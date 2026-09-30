<?php

namespace Tests\Feature;

use App\Domain\DomainConflict;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Queries\ReportQuery;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PDOException;
use RuntimeException;
use Tests\Support\EnforcedCsrfToken;
use Tests\TestCase;

final class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_edit_uses_the_submitted_version_and_rejects_stale_or_missing_versions(): void
    {
        $manager = User::factory()->manager()->create();
        $category = app(CategoryService::class)->create($manager, 'Original');
        $this->actingAs($manager)->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Renamed', 'expected_version' => 1])
            ->assertOk()->assertJsonPath('data.name', 'renamed')->assertJsonPath('data.version', '2');
        $this->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Stale', 'expected_version' => 1])
            ->assertConflict()->assertJsonPath('error.code', 'version_conflict');
        $this->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Missing'])->assertUnprocessable();
        $this->patch("/categories/{$category->id}", ['name' => 'Browser edit', 'expected_version' => 2])->assertRedirect();
        $this->assertSame('browser edit', $category->fresh()->name);
        $this->assertSame(3, $category->fresh()->version);
    }

    public function test_product_can_be_edited_in_its_archived_category_but_cannot_be_reassigned_to_another_archived_category(): void
    {
        [$manager, $product] = $this->product();
        $categories = app(CategoryService::class);
        $categories->setArchived($manager, $product->category, true, 1);
        $other = $categories->create($manager, 'Other');
        $categories->setArchived($manager, $other, true, 1);
        $data = ['category_id' => $product->category_id, 'sku' => $product->sku, 'name' => 'Updated', 'unit_price' => '2.00', 'reorder_level' => 0, 'expected_version' => 1];
        $this->actingAs($manager)->patchJson("/api/v1/products/{$product->id}", $data)->assertOk()->assertJsonPath('data.name', 'Updated');
        $this->get("/products/{$product->id}")->assertOk()->assertSee('value="'.$product->category_id.'"', false);
        $this->patchJson("/api/v1/products/{$product->id}", [...$data, 'category_id' => $other->id, 'expected_version' => 2])
            ->assertConflict()->assertJsonPath('error.code', 'category_inactive');
        $this->assertSame($data['category_id'], $product->fresh()->category_id);
    }

    public function test_invalid_queries_and_effective_date_ranges_return_validation_errors(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager);
        foreach (['/api/v1/products?q[]=bad', '/api/v1/products?sort[]=name', '/api/v1/products?per_page=101', '/api/v1/sales?page=0', '/api/v1/reports/inventory?state=typo', '/api/v1/reports/sales-value?start=2026-02-30&end=2026-03-01', '/api/v1/reports/sales-value?start=2999-01-01'] as $url) {
            $this->getJson($url)->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');
        }
        $this->from('/reports')->get('/reports?start=bad&end=2026-09-30')->assertRedirect('/reports')->assertSessionHasErrors('start');
        $this->getJson('/api/v1/reports/sales-value')->assertOk()->assertJsonPath('data.total', '0.00');
    }

    public function test_top_products_groups_by_identity_and_keeps_snapshot_prices_and_receipts(): void
    {
        [$manager, $product] = $this->product('10.00');
        $stock = app(StockService::class);
        $sales = app(SaleService::class);
        $stock->change($manager, $product, 'restock', 10, 'Opening stock', (string) Str::uuid());
        $first = $sales->record($manager, null, $this->cart($product, 2), (string) Str::uuid());
        $current = $product->fresh();
        $updated = app(ProductService::class)->update($manager, $current, ['category_id' => $current->category_id, 'sku' => 'RENAMED', 'name' => 'Renamed product', 'unit_price' => '12.00', 'reorder_level' => 0], $current->version);
        $second = $sales->record($manager, null, $this->cart($updated, 3), (string) Str::uuid());
        $third = $sales->record($manager, null, $this->cart($updated, 1), (string) Str::uuid());
        $sales->cancel($manager, $third, 'Cancelled fixture');
        $reports = app(ReportQuery::class);
        $top = $reports->topProducts('2000-01-01', '2999-01-01');
        $this->assertCount(1, $top);
        $this->assertSame(5, (int) $top[0]->units);
        $this->assertSame('56.00', $top[0]->recorded_value);
        $this->assertSame('Renamed product', $top[0]->product_name);
        $this->assertSame('56.00', $reports->recordedSalesValue('2000-01-01', '2999-01-01'));
        $this->assertSame('Audit product', $first->items[0]->product_name);
        $this->assertSame('10.00', $first->items[0]->unit_price);
        $this->assertSame('36.00', $sales->total($second));
    }

    public function test_sales_value_uses_half_open_dhaka_boundaries(): void
    {
        [$manager, $product] = $this->product('1.25');
        app(StockService::class)->change($manager, $product, 'restock', 10, 'Opening stock', (string) Str::uuid());
        foreach (['2026-09-09 17:59:59', '2026-09-09 18:00:00', '2026-09-10 17:59:59', '2026-09-10 18:00:00'] as $time) {
            $sale = app(SaleService::class)->record($manager, null, $this->cart($product, 1), (string) Str::uuid());
            DB::table('sales')->where('id', $sale->id)->update(['created_at' => $time]);
        }
        $this->assertSame('2.50', app(ReportQuery::class)->recordedSalesValue('2026-09-10', '2026-09-10'));
        $this->assertSame('1.25', app(ReportQuery::class)->recordedSalesValue('2026-09-11', '2026-09-11'));
        $this->assertSame('0.00', app(ReportQuery::class)->recordedSalesValue('2026-09-12', '2026-09-12'));
    }

    public function test_report_api_serializes_ids_dates_and_aggregates_without_precision_loss(): void
    {
        [$manager, $product] = $this->product();
        app(StockService::class)->change($manager, $product, 'restock', 2, 'Opening stock', (string) Str::uuid());
        app(SaleService::class)->record($manager, null, $this->cart($product, 1), (string) Str::uuid());
        $this->actingAs($manager)->getJson('/api/v1/reports/inventory')->assertOk()->assertJsonPath('data.0.id', (string) $product->id);
        $this->getJson('/api/v1/reports/reconciliation')->assertOk()
            ->assertJsonPath('data.balances.0.product_id', (string) $product->id)->assertJsonPath('data.balances.0.difference', '0');
        $this->getJson('/api/v1/reports/top-products')->assertOk()
            ->assertJsonPath('data.0.product_id', (string) $product->id)->assertJsonPath('data.0.units', '1')->assertJsonPath('data.0.recorded_value', '10.00');
        $current = $product->fresh();
        app(StockService::class)->change($manager, $current, 'adjustment', -1, 'Archive fixture', (string) Str::uuid(), $current->version);
        $empty = $product->fresh();
        app(ProductService::class)->setArchived($manager, $empty, true, $empty->version);
        $json = $this->getJson('/api/v1/reports/inventory?state=archived')->assertOk()->json('data.0.archived_at');
        $this->assertMatchesRegularExpression('/T.*\+00:00$/', $json);
    }

    public function test_failure_after_stock_update_rolls_back_header_items_stock_version_and_movements(): void
    {
        [$manager, $product] = $this->product();
        app(StockService::class)->change($manager, $product, 'restock', 3, 'Opening stock', (string) Str::uuid());
        $version = $product->fresh()->version;
        StockMovement::creating(fn () => throw new RuntimeException('Injected movement failure'));
        try {
            app(SaleService::class)->record($manager, null, $this->cart($product, 1), (string) Str::uuid());
            $this->fail('Expected the injected failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected movement failure', $exception->getMessage());
        } finally {
            StockMovement::flushEventListeners();
        }
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleItem::count());
        $this->assertSame(3, $product->fresh()->stock_on_hand);
        $this->assertSame($version, $product->fresh()->version);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_unknown_write_fields_and_non_integer_quantities_are_rejected(): void
    {
        [$manager, $product] = $this->product();
        $this->actingAs($manager)->postJson('/api/v1/categories', ['name' => 'Spoofed', 'created_by' => 123])->assertUnprocessable();
        foreach ([true, 1.5, '1e2', '18446744073709551616'] as $quantity) {
            $this->postJson("/api/v1/products/{$product->id}/restocks", ['quantity' => $quantity, 'note' => 'Invalid fixture', 'request_key' => (string) Str::uuid()])->assertUnprocessable();
        }
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(1, Category::count());
        try {
            app(SaleService::class)->record($manager, null, [['product_id' => $product->id, 'quantity' => 1.5, 'expected_unit_price' => '10.00']], (string) Str::uuid());
            $this->fail('Fractional quantity must not be silently truncated.');
        } catch (DomainConflict $e) {
            $this->assertSame('invalid_cart', $e->errorCode);
        }
        $this->assertSame(0, Sale::count());
    }

    public function test_csrf_denial_is_enforced_with_testing_bypass_disabled_and_writes_nothing(): void
    {
        $manager = User::factory()->manager()->create();
        $this->app->bind(ValidateCsrfToken::class, EnforcedCsrfToken::class);
        $this->actingAs($manager)->postJson('/api/v1/categories', ['name' => 'Blocked'])
            ->assertStatus(419)->assertJsonPath('error.code', 'csrf_failed');
        $this->assertSame(0, Category::count());
        $this->withSession(['_token' => 'test-csrf-token'])->postJson('/api/v1/categories', ['name' => 'Allowed'], ['X-CSRF-TOKEN' => 'test-csrf-token'])->assertCreated();
    }

    public function test_api_authentication_returns_json_even_without_accept_header(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.test', 'password' => 'Audit password 123']);
        $this->post('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonPath('error.code', 'invalid_credentials');
        $this->post('/api/v1/login', ['email' => $user->email, 'password' => 'Audit password 123'])
            ->assertOk()->assertJsonPath('data.user.id', (string) $user->id);
        $this->post('/api/v1/logout')->assertNoContent();
    }

    public function test_csv_neutralizes_formula_text_quotes_fields_and_denies_clerks(): void
    {
        [$manager, $product] = $this->product();
        Product::whereKey($product->id)->update(['name' => ' =SUM(1,2)']);
        $response = $this->actingAs($manager)->get('/reports/inventory.csv')->assertOk();
        $lines = explode("\n", trim($response->streamedContent()));
        $record = str_getcsv($lines[1]);
        $this->assertSame("' =SUM(1,2)", $record[2]);
        $this->actingAs(User::factory()->create())->get('/api/v1/reports/inventory.csv')->assertForbidden();
    }

    public function test_sale_form_restores_original_cart_key_and_expected_prices_after_a_conflict(): void
    {
        [$manager, $product] = $this->product();
        $key = (string) Str::uuid();
        $this->actingAs($manager)->from('/sales/create')->post('/sales', ['request_key' => $key, 'items' => $this->cart($product, 2)])
            ->assertRedirect('/sales/create')->assertSessionHasInput('request_key', $key);
        $this->get('/sales/create')->assertOk()->assertSee('value="'.$key.'"', false)
            ->assertSee('value="10.00"', false)->assertSee('value="2"', false)->assertSee('Add product')->assertSee('Use current prices');
        $this->withSession(['_old_input' => ['request_key' => [], 'customer_id' => [], 'items' => ['bad', ['quantity' => []]]]])
            ->get('/sales/create')->assertOk();
    }

    public function test_exhausted_contention_is_a_safe_retry_conflict_and_sql_bindings_are_not_logged(): void
    {
        $manager = User::factory()->manager()->create();
        $pdo = new PDOException('Lock wait timeout exceeded');
        $pdo->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded'];
        Route::get('/api/v1/audit-contention', fn () => throw new QueryException('mariadb', 'update customers set email = ?', ['private-fixture@example.test'], $pdo));
        Log::shouldReceive('error')->once()->with('Database request failed.', \Mockery::on(fn ($context) => $context['driver_code'] === 1205 && ! str_contains(json_encode($context), 'private-fixture')));
        $this->actingAs($manager)->getJson('/api/v1/audit-contention')->assertConflict()
            ->assertJsonPath('error.code', 'retry_later')->assertDontSee('private-fixture');
    }

    public function test_stock_forms_preserve_only_the_submitted_operation_and_its_retry_version(): void
    {
        [$manager, $product] = $this->product();
        $key = (string) Str::uuid();
        $this->actingAs($manager)->from("/products/{$product->id}")->post("/products/{$product->id}/adjustments", [
            'quantity_delta' => -1, 'note' => 'Count correction', 'request_key' => $key, 'expected_version' => 1,
        ])->assertRedirect()->assertSessionHasInput('request_key', $key);
        app(StockService::class)->change($manager, $product, 'restock', 2, 'Concurrent receipt', (string) Str::uuid());
        $view = $this->get("/products/{$product->id}")->assertOk();
        $view->assertViewHas('adjustmentForm', fn ($form) => $form['request_key'] === $key && $form['quantity'] === -1 && $form['expected_version'] === 1 && $form['note'] === 'Count correction');
        $view->assertViewHas('restockForm', fn ($form) => $form['request_key'] !== $key && $form['quantity'] === '' && $form['note'] === '');
        $this->withSession(['_old_input' => ['request_key' => [], 'note' => [], 'quantity_delta' => [], 'expected_version' => []]])
            ->get("/products/{$product->id}")->assertOk();
    }

    public function test_malformed_login_input_does_not_crash_the_following_login_page(): void
    {
        $this->from('/login')->post('/login', ['email' => ['bad'], 'password' => 'fixture'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->get('/login')->assertOk();
        $this->postJson('/api/v1/login', ['email' => 'staff@example.test', 'password' => 'fixture', 'role' => 'manager'])
            ->assertUnprocessable()->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_price_review_creates_a_new_sale_while_unchanged_replay_keeps_the_original_receipt(): void
    {
        [$manager, $product] = $this->product('9.00');
        app(StockService::class)->change($manager, $product, 'restock', 5, 'Opening stock', (string) Str::uuid());
        $key = (string) Str::uuid();
        $cart = $this->cart($product, 2);
        $original = app(SaleService::class)->record($manager, null, $cart, $key);
        $current = $product->fresh();
        $updated = app(ProductService::class)->update($manager, $current, [
            'category_id' => $current->category_id, 'sku' => $current->sku, 'name' => $current->name,
            'unit_price' => '10.00', 'reorder_level' => 2,
        ], $current->version);
        $this->actingAs($manager)->postJson('/api/v1/sales', ['request_key' => $key, 'items' => $cart])
            ->assertOk()->assertJsonPath('data.id', (string) $original->id)->assertJsonPath('data.total', '18.00')->assertJsonPath('replayed', true);
        $this->postJson('/api/v1/sales', ['request_key' => (string) Str::uuid(), 'items' => $cart])
            ->assertConflict()->assertJsonPath('error.code', 'price_changed');
        $this->assertSame(1, Sale::count());
        $this->postJson('/api/v1/sales', ['request_key' => (string) Str::uuid(), 'items' => $this->cart($updated, 2)])
            ->assertCreated()->assertJsonPath('data.total', '20.00')->assertJsonPath('replayed', false);
        $this->assertSame(1, $product->fresh()->stock_on_hand);
        $this->assertSame(3, StockMovement::count());
    }

    private function product(string $price = '10.00'): array
    {
        $manager = User::factory()->manager()->create();
        $category = app(CategoryService::class)->create($manager, 'Audit category');
        $product = app(ProductService::class)->create($manager, ['category_id' => $category->id, 'sku' => 'AUDIT-1', 'name' => 'Audit product', 'unit_price' => $price, 'reorder_level' => 2]);

        return [$manager, $product];
    }

    private function cart(Product $product, int $quantity): array
    {
        return [['product_id' => $product->id, 'quantity' => $quantity, 'expected_unit_price' => (string) $product->unit_price]];
    }
}
