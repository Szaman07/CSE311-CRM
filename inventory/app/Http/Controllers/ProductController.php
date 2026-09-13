<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function index(Request $request): View
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

    public function show(Product $product): View
    {
        return view('products.show', [
            'product' => $product->load('category'),
            'categories' => Category::whereNull('archived_at')->orderBy('name')->orderBy('id')->get(),
            'movements' => $product->movements()->with(['actor', 'saleItem'])->latest('created_at')->latest('id')->paginate(20),
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
