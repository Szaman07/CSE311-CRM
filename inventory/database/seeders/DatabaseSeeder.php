<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\CustomerService;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\StockService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data is forbidden in production.');
        }
        if (! env('NEXASTOCK_DEMO_PASSWORD')) {
            throw new RuntimeException('Set NEXASTOCK_DEMO_PASSWORD in the local environment.');
        }
        if (User::exists() || Category::exists() || Product::exists() || Customer::exists() || Sale::exists() || StockMovement::exists()) {
            throw new RuntimeException('Seeder requires an empty business schema and will not overwrite existing data.');
        }

        $manager = User::create(['name' => 'NexaStock Manager', 'email' => 'manager@nexastock.local', 'password' => Hash::make(env('NEXASTOCK_DEMO_PASSWORD')), 'role' => 'manager', 'is_active' => true]);
        User::create(['name' => 'Sales Clerk One', 'email' => 'clerk@nexastock.local', 'password' => Hash::make(env('NEXASTOCK_DEMO_PASSWORD')), 'role' => 'sales_clerk', 'is_active' => true]);
        User::create(['name' => 'Sales Clerk Two', 'email' => 'clerk2@nexastock.local', 'password' => Hash::make(env('NEXASTOCK_DEMO_PASSWORD')), 'role' => 'sales_clerk', 'is_active' => true]);

        $categories = app(CategoryService::class);
        $products = app(ProductService::class);
        $customers = app(CustomerService::class);
        $stock = app(StockService::class);
        $sales = app(SaleService::class);

        CarbonImmutable::setTestNow('2026-09-01 02:00:00 UTC');
        $stationery = $categories->create($manager, 'stationery');
        $office = $categories->create($manager, 'office');
        $pen = $products->create($manager, ['category_id' => $stationery->id, 'sku' => 'PEN-001', 'name' => 'Ballpoint Pen', 'unit_price' => '10.00', 'reorder_level' => 3]);
        $notebook = $products->create($manager, ['category_id' => $stationery->id, 'sku' => 'NOTE-001', 'name' => 'Notebook', 'unit_price' => '50.00', 'reorder_level' => 2]);
        $eraser = $products->create($manager, ['category_id' => $stationery->id, 'sku' => 'ERASE-001', 'name' => 'Eraser', 'unit_price' => '5.00', 'reorder_level' => 2]);
        $products->create($manager, ['category_id' => $office->id, 'sku' => 'MARK-001', 'name' => 'Unused Marker', 'unit_price' => '25.00', 'reorder_level' => 0]);
        $customerA = $customers->create($manager, ['full_name' => 'Customer A', 'email' => 'a@example.test', 'phone' => null]);
        $customers->create($manager, ['full_name' => 'Customer B', 'email' => null, 'phone' => null]);

        foreach ([[$pen, 10, '00000000-0000-4000-8000-000000000001'], [$notebook, 5, '00000000-0000-4000-8000-000000000002'], [$eraser, 2, '00000000-0000-4000-8000-000000000003']] as [$product, $quantity, $key]) {
            $stock->change($manager, $product, 'restock', $quantity, 'Opening balance', $key);
        }

        CarbonImmutable::setTestNow('2026-09-02 04:00:00 UTC');
        $sales->record($manager, $customerA->id, [
            ['product_id' => $pen->id, 'quantity' => 2, 'expected_unit_price' => '10.00'],
            ['product_id' => $notebook->id, 'quantity' => 1, 'expected_unit_price' => '50.00'],
        ], '00000000-0000-4000-8000-000000000004');

        CarbonImmutable::setTestNow('2026-09-03 04:00:00 UTC');
        $cancelled = $sales->record($manager, null, [['product_id' => $pen->id, 'quantity' => 1, 'expected_unit_price' => '10.00']], '00000000-0000-4000-8000-000000000005');
        CarbonImmutable::setTestNow('2026-09-03 05:00:00 UTC');
        $sales->cancel($manager, $cancelled, 'Demonstration cancellation');

        CarbonImmutable::setTestNow();
    }
}
