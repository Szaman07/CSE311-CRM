<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\RecordSaleRequest;
use App\Http\Requests\StockChangeRequest;
use App\Http\Requests\VersionRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\MovementResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SaleResource;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Queries\ReportQuery;
use App\Services\CategoryService;
use App\Services\CustomerService;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\StockService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ApiV1Controller extends Controller
{
    public function categories(Request $request): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::orderBy('name')->orderBy('id')->paginate($this->perPage($request)));
    }

    public function createCategory(CategoryRequest $request, CategoryService $service): JsonResponse
    {
        return (new CategoryResource($service->create($request->user(), $request->validated('name'))))->response()->setStatusCode(201);
    }

    public function updateCategory(CategoryRequest $request, Category $category, CategoryService $service): CategoryResource
    {
        return new CategoryResource($service->update($request->user(), $category, $request->validated('name'), (int) $request->validated('expected_version')));
    }

    public function archiveCategory(VersionRequest $request, Category $category, CategoryService $service): CategoryResource
    {
        return new CategoryResource($service->setArchived($request->user(), $category, true, (int) $request->validated('expected_version')));
    }

    public function restoreCategory(VersionRequest $request, Category $category, CategoryService $service): CategoryResource
    {
        return new CategoryResource($service->setArchived($request->user(), $category, false, (int) $request->validated('expected_version')));
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $sorts = ['name' => 'name', 'sku' => 'sku', 'stock' => 'stock_on_hand', 'price' => 'unit_price'];
        $state = $request->input('state', 'active');
        $query = Product::with('category')
            ->when($state === 'active', fn (Builder $q) => $q->whereNull('archived_at'))
            ->when($state === 'archived', fn (Builder $q) => $q->whereNotNull('archived_at'))
            ->when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('name', 'like', '%'.mb_substr($request->input('q'), 0, 100).'%')->orWhere('sku', 'like', '%'.mb_substr($request->input('q'), 0, 100).'%')))
            ->orderBy($sorts[$request->input('sort')] ?? 'name')->orderBy('id');

        return ProductResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function product(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }

    public function createProduct(ProductRequest $request, ProductService $service): JsonResponse
    {
        return (new ProductResource($service->create($request->user(), $request->validated())->load('category')))->response()->setStatusCode(201);
    }

    public function updateProduct(ProductRequest $request, Product $product, ProductService $service): ProductResource
    {
        return new ProductResource($service->update($request->user(), $product, $request->validated(), (int) $request->validated('expected_version'))->load('category'));
    }

    public function archiveProduct(VersionRequest $request, Product $product, ProductService $service): ProductResource
    {
        return new ProductResource($service->setArchived($request->user(), $product, true, (int) $request->validated('expected_version'))->load('category'));
    }

    public function restoreProduct(VersionRequest $request, Product $product, ProductService $service): ProductResource
    {
        return new ProductResource($service->setArchived($request->user(), $product, false, (int) $request->validated('expected_version'))->load('category'));
    }

    public function restock(StockChangeRequest $request, Product $product, StockService $service): JsonResponse
    {
        $movement = $service->change($request->user(), $product, 'restock', (int) $request->validated('quantity'), $request->validated('note'), $request->validated('request_key'));

        return (new MovementResource($movement->load('actor')))->additional(['replayed' => ! $movement->wasRecentlyCreated])->response()->setStatusCode($movement->wasRecentlyCreated ? 201 : 200);
    }

    public function adjust(StockChangeRequest $request, Product $product, StockService $service): JsonResponse
    {
        $movement = $service->change($request->user(), $product, 'adjustment', (int) $request->validated('quantity_delta'), $request->validated('note'), $request->validated('request_key'), (int) $request->validated('expected_version'));

        return (new MovementResource($movement->load('actor')))->additional(['replayed' => ! $movement->wasRecentlyCreated])->response()->setStatusCode($movement->wasRecentlyCreated ? 201 : 200);
    }

    public function movements(Request $request, Product $product): AnonymousResourceCollection
    {
        return MovementResource::collection($product->movements()->with(['actor', 'saleItem'])->orderByDesc('created_at')->orderByDesc('id')->paginate($this->perPage($request)));
    }

    public function customers(Request $request): AnonymousResourceCollection
    {
        $query = Customer::when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('full_name', 'like', '%'.mb_substr($request->input('q'), 0, 100).'%')->orWhere('email', 'like', '%'.mb_substr($request->input('q'), 0, 100).'%')))
            ->orderBy('full_name')->orderBy('id');

        return CustomerResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function customer(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function customerSales(Request $request, Customer $customer, ReportQuery $reports): JsonResponse
    {
        $page = $reports->customerHistory($customer, $this->perPage($request));

        return response()->json(['data' => collect($page->items())->map(fn ($sale) => [
            'id' => (string) $sale->id,
            'status' => $sale->status,
            'total' => (string) $sale->total,
            'created_at' => CarbonImmutable::parse($sale->created_at, 'UTC')->toIso8601String(),
            'cancelled_at' => $sale->cancelled_at ? CarbonImmutable::parse($sale->cancelled_at, 'UTC')->toIso8601String() : null,
        ]), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function createCustomer(CustomerRequest $request, CustomerService $service): JsonResponse
    {
        return (new CustomerResource($service->create($request->user(), $request->validated())))->response()->setStatusCode(201);
    }

    public function updateCustomer(CustomerRequest $request, Customer $customer, CustomerService $service): CustomerResource
    {
        return new CustomerResource($service->update($request->user(), $customer, $request->validated(), (int) $request->validated('expected_version')));
    }

    public function archiveCustomer(VersionRequest $request, Customer $customer, CustomerService $service): CustomerResource
    {
        return new CustomerResource($service->setArchived($request->user(), $customer, true, (int) $request->validated('expected_version')));
    }

    public function restoreCustomer(VersionRequest $request, Customer $customer, CustomerService $service): CustomerResource
    {
        return new CustomerResource($service->setArchived($request->user(), $customer, false, (int) $request->validated('expected_version')));
    }

    public function sales(Request $request): AnonymousResourceCollection
    {
        $query = Sale::with(['customer', 'items'])->when(in_array($request->input('status'), ['completed', 'cancelled'], true), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = mb_substr((string) $request->input('q'), 0, 100);
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('id', ctype_digit($term) ? (int) $term : -1)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$term.'%'))
                        ->orWhereHas('items', fn (Builder $item) => $item->where('product_name', 'like', '%'.$term.'%')->orWhere('product_sku', 'like', '%'.$term.'%'));
                });
            })
            ->orderByDesc('created_at')->orderByDesc('id');

        return SaleResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function sale(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load(['customer', 'items']));
    }

    public function createSale(RecordSaleRequest $request, SaleService $service): JsonResponse
    {
        $sale = $service->record($request->user(), $request->validated('customer_id'), $request->validated('items'), $request->validated('request_key'));
        $replayed = ! $sale->wasRecentlyCreated;

        return (new SaleResource($sale))->additional(['replayed' => $replayed])->response()->setStatusCode($replayed ? 200 : 201);
    }

    public function cancelSale(CancelSaleRequest $request, Sale $sale, SaleService $service): SaleResource
    {
        return new SaleResource($service->cancel($request->user(), $sale, $request->validated('reason')));
    }

    public function report(Request $request, string $report, ReportQuery $reports): JsonResponse
    {
        $today = now(config('nexastock.display_timezone'))->toDateString();
        $dates = in_array($report, ['sales-value', 'top-products'], true)
            ? $request->validate(['start' => ['nullable', 'date_format:Y-m-d'], 'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start']])
            : [];
        $start = $dates['start'] ?? now(config('nexastock.display_timezone'))->subDays(29)->toDateString();
        $end = $dates['end'] ?? $today;
        $data = match ($report) {
            'inventory' => $reports->inventory($request->input('state', 'active')),
            'low-stock' => $reports->lowStock(),
            'sales-value' => ['total' => $reports->recordedSalesValue($start, $end), 'currency' => 'BDT'],
            'top-products' => $reports->topProducts($start, $end, min(100, max(1, (int) $request->input('limit', 10)))),
            'reconciliation' => $reports->reconciliation(),
            default => abort(404),
        };

        return response()->json(['data' => $data]);
    }

    private function perPage(Request $request): int
    {
        return min(100, max(1, (int) $request->input('per_page', 20)));
    }
}
