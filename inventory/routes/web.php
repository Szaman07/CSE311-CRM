<?php

use App\Http\Controllers\Api\ApiV1Controller;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');

Route::middleware(['auth', 'session.lifetime', 'active'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
    Route::post('/categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');
    Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::post('/products/{product}/restocks', [StockController::class, 'restock'])->name('products.restocks');
    Route::post('/products/{product}/adjustments', [StockController::class, 'adjust'])->name('products.adjustments');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers/{customer}/archive', [CustomerController::class, 'archive'])->name('customers.archive');
    Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/inventory.csv', [ReportController::class, 'inventoryCsv'])->name('reports.inventory.csv');
});

Route::prefix('api/v1')->name('api.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login');
    Route::middleware(['auth', 'session.lifetime', 'active'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/me', [AuthController::class, 'current'])->name('me');

        Route::get('/categories', [ApiV1Controller::class, 'categories'])->name('categories.index');
        Route::post('/categories', [ApiV1Controller::class, 'createCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [ApiV1Controller::class, 'updateCategory'])->name('categories.update');
        Route::post('/categories/{category}/archive', [ApiV1Controller::class, 'archiveCategory'])->name('categories.archive');
        Route::post('/categories/{category}/restore', [ApiV1Controller::class, 'restoreCategory'])->name('categories.restore');

        Route::get('/products', [ApiV1Controller::class, 'products'])->name('products.index');
        Route::post('/products', [ApiV1Controller::class, 'createProduct'])->name('products.store');
        Route::get('/products/{product}', [ApiV1Controller::class, 'product'])->name('products.show');
        Route::patch('/products/{product}', [ApiV1Controller::class, 'updateProduct'])->name('products.update');
        Route::post('/products/{product}/archive', [ApiV1Controller::class, 'archiveProduct'])->name('products.archive');
        Route::post('/products/{product}/restore', [ApiV1Controller::class, 'restoreProduct'])->name('products.restore');
        Route::post('/products/{product}/restocks', [ApiV1Controller::class, 'restock'])->name('products.restocks');
        Route::post('/products/{product}/adjustments', [ApiV1Controller::class, 'adjust'])->name('products.adjustments');
        Route::get('/products/{product}/movements', [ApiV1Controller::class, 'movements'])->name('products.movements');

        Route::get('/customers', [ApiV1Controller::class, 'customers'])->name('customers.index');
        Route::post('/customers', [ApiV1Controller::class, 'createCustomer'])->name('customers.store');
        Route::get('/customers/{customer}', [ApiV1Controller::class, 'customer'])->name('customers.show');
        Route::get('/customers/{customer}/sales', [ApiV1Controller::class, 'customerSales'])->name('customers.sales');
        Route::patch('/customers/{customer}', [ApiV1Controller::class, 'updateCustomer'])->name('customers.update');
        Route::post('/customers/{customer}/archive', [ApiV1Controller::class, 'archiveCustomer'])->name('customers.archive');
        Route::post('/customers/{customer}/restore', [ApiV1Controller::class, 'restoreCustomer'])->name('customers.restore');

        Route::get('/sales', [ApiV1Controller::class, 'sales'])->name('sales.index');
        Route::post('/sales', [ApiV1Controller::class, 'createSale'])->name('sales.store');
        Route::get('/sales/{sale}', [ApiV1Controller::class, 'sale'])->name('sales.show');
        Route::post('/sales/{sale}/cancel', [ApiV1Controller::class, 'cancelSale'])->name('sales.cancel');

        Route::get('/reports/inventory.csv', [ReportController::class, 'inventoryCsv'])->name('reports.inventory.csv');
        Route::get('/reports/{report}', [ApiV1Controller::class, 'report'])->where('report', 'inventory|low-stock|sales-value|top-products|reconciliation')->name('reports.show');
    });
});
