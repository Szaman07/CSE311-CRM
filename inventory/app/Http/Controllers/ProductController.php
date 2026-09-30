<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\QueryRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function index(QueryRequest $request): View
    {
        $sorts = ['name' => 'name', 'sku' => 'sku', 'stock' => 'stock_on_hand', 'price' => 'unit_price'];
        $sort = $sorts[$request->input('sort')] ?? 'name';
        $state = $request->input('state', 'active');
        $products = Product::with('category')
            ->when($state === 'active', fn (Builder $q) => $q->whereNull('archived_at'))
            ->when($state === 'archived', fn (Builder $q) => $q->whereNotNull('archived_at'))
            ->when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('name', 'like', '%'.$request->string('q')->limit(100).'%')->orWhere('sku', 'like', '%'.$request->string('q')->limit(100).'%')))
            ->orderBy($sort)->orderBy('id')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 20))))->withQueryString();

        return view('products.index', ['products' => $products, 'categories' => Category::whereNull('archived_at')->orderBy('name')->get()]);
    }

    public function show(Request $request, Product $product): View
    {
        $old = $request->session()->getOldInput();
        $scalar = fn (string $field, string|int $default = '') => is_string($old[$field] ?? null) || is_int($old[$field] ?? null) ? $old[$field] : $default;
        $form = function (string $quantityField) use ($old, $scalar, $product): array {
            $submitted = array_key_exists($quantityField, $old);
            $key = $submitted ? $scalar('request_key') : '';

            return [
                'request_key' => Str::isUuid($key) ? $key : (string) Str::uuid(),
                'quantity' => $submitted ? $scalar($quantityField) : '',
                'note' => $submitted ? $scalar('note') : '',
                'expected_version' => $submitted ? $scalar('expected_version', $product->version) : $product->version,
            ];
        };

        return view('products.show', [
            'product' => $product->load('category'),
            'categories' => Category::whereNull('archived_at')->orWhere('id', $product->category_id)->orderBy('name')->orderBy('id')->get(),
            'movements' => $product->movements()->with(['actor', 'saleItem'])->latest('created_at')->latest('id')->paginate(20),
            'restockForm' => $form('quantity'),
            'adjustmentForm' => $form('quantity_delta'),
            'hasStockInput' => array_key_exists('quantity', $old) || array_key_exists('quantity_delta', $old),
        ]);
    }

    public function store(ProductRequest $request, ProductService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated());

        return back()->with('success', 'Product created with zero stock.');
    }

    public function update(ProductRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        $service->update($request->user(), $product, $request->validated(), (int) $request->validated('expected_version'));

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function archive(VersionRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        $service->setArchived($request->user(), $product, true, (int) $request->validated('expected_version'));

        return back()->with('success', 'Product archived.');
    }

    public function restore(VersionRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        $service->setArchived($request->user(), $product, false, (int) $request->validated('expected_version'));

        return back()->with('success', 'Product restored.');
    }
}
