<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'activeProducts' => Product::whereNull('archived_at')->count(),
            'lowStock' => Product::whereNull('archived_at')->whereColumn('stock_on_hand', '<=', 'reorder_level')->count(),
            'completedSales' => Sale::where('status', 'completed')->count(),
        ]);
    }
}
