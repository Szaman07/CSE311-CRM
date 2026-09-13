<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class SeedBenchmark extends Command
{
    protected $signature = 'benchmark:seed {--confirm=}';

    protected $description = 'Create the exact disposable NFR-04 fixture through domain services';

    public function handle(CategoryService $categories, ProductService $products, StockService $stock, SaleService $sales): int
    {
        if (app()->environment() !== 'benchmark'
            || config('database.connections.'.config('database.default').'.database') !== 'nexastock_benchmark'
            || $this->option('confirm') !== 'RESET_NEXASTOCK_BENCHMARK') {
            throw new RuntimeException('Requires APP_ENV=benchmark, database nexastock_benchmark, and --confirm=RESET_NEXASTOCK_BENCHMARK.');
        }
        $password = env('NEXASTOCK_BENCH_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set an untracked NEXASTOCK_BENCH_PASSWORD of at least 12 characters.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
        $manager = User::create(['name' => 'Benchmark Manager', 'email' => 'benchmark@nexastock.local', 'password' => Hash::make($password), 'role' => 'manager', 'is_active' => true]);
        $categoryRows = [];
        for ($i = 0; $i < 10; $i++) {
            $categoryRows[] = $categories->create($manager, sprintf('benchmark-%02d', $i));
        }
        $productRows = [];
        for ($i = 0; $i < 1000; $i++) {
            $product = $products->create($manager, ['category_id' => $categoryRows[$i % 10]->id, 'sku' => sprintf('BENCH-%04d', $i), 'name' => sprintf('Benchmark product %04d', $i), 'unit_price' => sprintf('%d.%02d', ($i % 1000) + 1, $i % 100), 'reorder_level' => 100]);
            $stock->change($manager, $product, 'restock', 100, 'Benchmark opening balance', (string) Str::uuid());
            $productRows[] = $product;
        }
        for ($saleNo = 0; $saleNo < 10000; $saleNo++) {
            $cart = [];
            for ($line = 0; $line < 5; $line++) {
                $product = $productRows[($saleNo * 5 + $line) % 1000];
                $cart[] = ['product_id' => $product->id, 'quantity' => 1, 'expected_unit_price' => (string) $product->unit_price];
            }
            $sales->record($manager, null, $cart, (string) Str::uuid());
            if (($saleNo + 1) % 500 === 0) {
                $this->info('Sales committed: '.($saleNo + 1));
            }
        }
        if (Sale::count() !== 10000 || SaleItem::count() !== 50000) {
            throw new RuntimeException('Benchmark fixture count verification failed.');
        }
        $this->info('Fixture ready: 1,000 products, 10,000 sales, 50,000 items.');

        return self::SUCCESS;
    }
}
