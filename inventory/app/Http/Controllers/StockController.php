<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockChangeRequest;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;

final class StockController extends Controller
{
    public function restock(StockChangeRequest $request, Product $product, StockService $service): RedirectResponse
    {
        $service->change($request->user(), $product, 'restock', (int) $request->validated('quantity'), $request->validated('note'), $request->validated('request_key'));

        return back()->with('success', 'Stock receipt recorded.');
    }

    public function adjust(StockChangeRequest $request, Product $product, StockService $service): RedirectResponse
    {
        $service->change($request->user(), $product, 'adjustment', (int) $request->validated('quantity_delta'), $request->validated('note'), $request->validated('request_key'), (int) $request->validated('expected_version'));

        return back()->with('success', 'Stock correction recorded.');
    }
}
