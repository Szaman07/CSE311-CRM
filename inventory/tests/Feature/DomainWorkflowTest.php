<?php

namespace Tests\Feature;

use App\Domain\DomainConflict;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Queries\ReportQuery;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DomainWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_and_sale_retries_are_idempotent_and_duplicate_cart_rows_merge(): void
    {
        [$manager, $product] = $this->product('10.00');
        $stock = app(StockService::class);
        $key = (string) Str::uuid();
        $first = $stock->change($manager, $product, 'restock', 10, 'Opening stock', $key);
        $again = $stock->change($manager, $product->fresh(), 'restock', 10, 'Opening stock', strtoupper($key));
        $this->assertSame($first->id, $again->id);
        $this->assertSame(10, $product->fresh()->stock_on_hand);

        $saleKey = (string) Str::uuid();
        $cart = [
            ['product_id' => $product->id, 'quantity' => 2, 'expected_unit_price' => '10'],
            ['product_id' => $product->id, 'quantity' => 3, 'expected_unit_price' => '10.00'],
        ];
        $sale = app(SaleService::class)->record($manager, null, $cart, $saleKey);
        $replay = app(SaleService::class)->record($manager, null, array_reverse($cart), $saleKey);
        $this->assertSame($sale->id, $replay->id);
        $this->assertSame(1, $sale->items()->count());
        $this->assertSame(5, $sale->items()->first()->quantity);
        $this->assertSame(5, $product->fresh()->stock_on_hand);
        $this->assertSame(2, StockMovement::count());
    }

    public function test_idempotency_key_reuse_with_different_intent_is_rejected(): void
    {
        [$manager, $product] = $this->product();
        $key = (string) Str::uuid();
        app(StockService::class)->change($manager, $product, 'restock', 2, 'First intent', $key);

        try {
            app(StockService::class)->change($manager, $product->fresh(), 'restock', 3, 'Second intent', $key);
            $this->fail('Expected an idempotency conflict.');
        } catch (DomainConflict $e) {
            $this->assertSame('idempotency_conflict', $e->errorCode);
        }
        $this->assertSame(2, $product->fresh()->stock_on_hand);
    }

    public function test_failed_multi_item_sale_rolls_every_write_back(): void
    {
        [$manager, $first] = $this->product('10.00', 'A-1');
        $category = $first->category;
        $second = app(ProductService::class)->create($manager, ['category_id' => $category->id, 'sku' => 'B-1', 'name' => 'Second', 'unit_price' => '4.00', 'reorder_level' => 0]);
        app(StockService::class)->change($manager, $first, 'restock', 3, 'Initial', (string) Str::uuid());
        app(StockService::class)->change($manager, $second, 'restock', 1, 'Initial', (string) Str::uuid());
        $beforeMovements = StockMovement::count();

        try {
            app(SaleService::class)->record($manager, null, [
                ['product_id' => $first->id, 'quantity' => 2, 'expected_unit_price' => '10.00'],
                ['product_id' => $second->id, 'quantity' => 2, 'expected_unit_price' => '4.00'],
            ], (string) Str::uuid());
            $this->fail('Expected insufficient stock.');
        } catch (DomainConflict $e) {
            $this->assertSame('insufficient_stock', $e->errorCode);
        }

        $this->assertSame(3, $first->fresh()->stock_on_hand);
        $this->assertSame(1, $second->fresh()->stock_on_hand);
        $this->assertSame(0, Sale::count());
        $this->assertSame($beforeMovements, StockMovement::count());
    }

    public function test_cancel_restores_an_archived_product_exactly_once(): void
    {
        [$manager, $product] = $this->product('12.50');
        app(StockService::class)->change($manager, $product, 'restock', 2, 'Initial', (string) Str::uuid());
        $sale = app(SaleService::class)->record($manager, null, [['product_id' => $product->id, 'quantity' => 2, 'expected_unit_price' => '12.50']], (string) Str::uuid());
        $empty = $product->fresh();
        app(ProductService::class)->setArchived($manager, $empty, true, $empty->version);

        $cancelled = app(SaleService::class)->cancel($manager, $sale, 'Customer return');
        app(SaleService::class)->cancel($manager, $cancelled, 'Repeated click');

        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock_on_hand);
        $this->assertNotNull($product->fresh()->archived_at);
        $this->assertSame(1, StockMovement::where('reason', 'cancellation')->count());
    }

    public function test_version_conflict_database_checks_and_reconciliation_are_enforced(): void
    {
        [$manager, $product] = $this->product();
        $displayedVersion = $product->version;
        app(StockService::class)->change($manager, $product, 'restock', 4, 'Initial', (string) Str::uuid());
        try {
            app(ProductService::class)->update($manager, $product, ['category_id' => $product->category_id, 'sku' => $product->sku, 'name' => 'Stale edit', 'unit_price' => '2.00', 'reorder_level' => 0], $displayedVersion);
            $this->fail('Expected stale edit rejection.');
        } catch (DomainConflict $e) {
            $this->assertSame('version_conflict', $e->errorCode);
        }

        try {
            Product::whereKey($product->id)->update(['stock_on_hand' => -1]);
            $this->fail('Expected the database stock constraint to reject the write.');
        } catch (QueryException) {
            $this->assertSame(4, $product->fresh()->stock_on_hand);
        }

        $report = app(ReportQuery::class)->reconciliation();
        $this->assertTrue($report['balances']->every(fn ($row) => (int) $row->difference === 0));
        $this->assertCount(0, $report['sale_link_issues']);
        $this->assertCount(0, $report['empty_sale_ids']);
    }

    private function product(string $price = '1.00', string $sku = 'TEST-1'): array
    {
        $manager = User::factory()->manager()->create();
        $category = app(CategoryService::class)->create($manager, 'Test Category');
        $product = app(ProductService::class)->create($manager, ['category_id' => $category->id, 'sku' => $sku, 'name' => 'Test Product', 'unit_price' => $price, 'reorder_level' => 0]);

        return [$manager, $product];
    }
}
