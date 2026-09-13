<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StockService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class ConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        parent::tearDown();
    }

    public function test_two_independent_sales_cannot_oversell_the_last_unit(): void
    {
        [$manager, $product] = $this->stockedProduct(1);
        $results = $this->race($manager, $product, [(string) Str::uuid(), (string) Str::uuid()]);

        $this->assertSame(['completed', 'conflict'], collect($results)->pluck('result')->sort()->values()->all());
        $this->assertSame(['insufficient_stock'], collect($results)->where('result', 'conflict')->pluck('code')->all());
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(1, Sale::count());
    }

    public function test_concurrent_same_key_replays_one_committed_sale(): void
    {
        [$manager, $product] = $this->stockedProduct(1);
        $key = (string) Str::uuid();
        $results = $this->race($manager, $product, [$key, $key]);

        $this->assertSame(['completed', 'completed'], collect($results)->pluck('result')->sort()->values()->all());
        $this->assertCount(1, collect($results)->pluck('sale_id')->unique());
        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(1, Sale::count());
    }

    private function stockedProduct(int $stock): array
    {
        $manager = User::factory()->manager()->create();
        $category = app(CategoryService::class)->create($manager, 'Concurrency');
        $product = app(ProductService::class)->create($manager, ['category_id' => $category->id, 'sku' => 'RACE-1', 'name' => 'Last unit', 'unit_price' => '9.99', 'reorder_level' => 1]);
        app(StockService::class)->change($manager, $product, 'restock', $stock, 'Race fixture', (string) Str::uuid());

        return [$manager, $product];
    }

    private function race(User $manager, Product $product, array $keys): array
    {
        $dir = storage_path('framework/testing/'.Str::uuid());
        mkdir($dir, 0777, true);
        $processes = [];

        DB::beginTransaction();
        Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
        try {
            foreach ($keys as $index => $key) {
                $ready = $dir."/ready-{$index}";
                $process = new Process([PHP_BINARY, base_path('tests/Support/sale_worker.php'), (string) $manager->id, (string) $product->id, '1', '9.99', $key, $ready], base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(15);
                $process->start();
                $processes[] = [$process, $ready];
            }

            $deadline = microtime(true) + 5;
            while (collect($processes)->contains(fn ($entry) => ! file_exists($entry[1])) && microtime(true) < $deadline) {
                usleep(20_000);
            }
            $this->assertTrue(collect($processes)->every(fn ($entry) => file_exists($entry[1])), 'Both independent workers reached the coordinated race.');
            usleep(150_000);
            $this->assertTrue(collect($processes)->every(fn ($entry) => $entry[0]->isRunning()), 'Both workers overlapped while the product row was locked.');
        } finally {
            DB::commit();
        }

        return array_map(function (array $entry): array {
            $entry[0]->wait();
            $this->assertSame(0, $entry[0]->getExitCode(), $entry[0]->getErrorOutput().$entry[0]->getOutput());

            return json_decode($entry[0]->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }, $processes);
    }
}
