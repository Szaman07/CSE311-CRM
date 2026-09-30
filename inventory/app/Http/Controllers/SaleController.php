<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSaleRequest;
use App\Http\Requests\QueryRequest;
use App\Http\Requests\RecordSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class SaleController extends Controller
{
    public function index(QueryRequest $request): View
    {
        $status = $request->input('status');
        $sales = Sale::with(['customer', 'creator', 'items'])
            ->when(in_array($status, ['completed', 'cancelled'], true), fn (Builder $q) => $q->where('status', $status))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = mb_substr((string) $request->input('q'), 0, 100);
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('id', ctype_digit($term) ? (int) $term : -1)
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('full_name', 'like', '%'.$term.'%'))
                        ->orWhereHas('items', fn (Builder $item) => $item->where('product_name', 'like', '%'.$term.'%')->orWhere('product_sku', 'like', '%'.$term.'%'));
                });
            })
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 20))))->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create(Request $request): View
    {
        $old = $request->session()->getOldInput();
        $cartItems = [];
        foreach (is_array($old['items'] ?? null) ? array_slice($old['items'], 0, 100) : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cartItems[] = array_map(fn ($value) => is_string($value) || is_int($value) ? $value : '', array_intersect_key($row, array_flip(['product_id', 'quantity', 'expected_unit_price'])));
        }

        return view('sales.create', [
            'products' => Product::whereNull('archived_at')->orderBy('name')->orderBy('id')->get(),
            'customers' => Customer::whereNull('archived_at')->orderBy('full_name')->orderBy('id')->get(),
            'cartItems' => $cartItems ?: [['quantity' => 1]],
            'selectedCustomer' => is_scalar($old['customer_id'] ?? null) ? (string) $old['customer_id'] : '',
            'requestKey' => is_string($old['request_key'] ?? null) && Str::isUuid($old['request_key']) ? $old['request_key'] : (string) Str::uuid(),
        ]);
    }

    public function store(RecordSaleRequest $request, SaleService $service): RedirectResponse
    {
        $sale = $service->record($request->user(), $request->validated('customer_id'), $request->validated('items'), $request->validated('request_key'));

        return redirect()->route('sales.show', $sale)->with('success', $sale->wasRecentlyCreated ? 'Sale recorded.' : 'Original sale returned; no duplicate stock change.');
    }

    public function show(Sale $sale, SaleService $service): View
    {
        $sale->load(['customer', 'creator', 'canceller', 'items']);

        return view('sales.show', ['sale' => $sale, 'total' => $service->total($sale)]);
    }

    public function cancel(CancelSaleRequest $request, Sale $sale, SaleService $service): RedirectResponse
    {
        $service->cancel($request->user(), $sale, $request->validated('reason'));

        return back()->with('success', 'Sale cancellation state confirmed.');
    }
}
