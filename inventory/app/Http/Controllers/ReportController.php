<?php

namespace App\Http\Controllers;

use App\Http\Requests\QueryRequest;
use App\Http\Requests\ReportRequest;
use App\Queries\ReportQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportQuery $reports): View
    {
        $start = $request->validated('start');
        $end = $request->validated('end');

        return view('reports.index', [
            'inventory' => $reports->inventory($request->input('state', 'active')),
            'lowStock' => $reports->lowStock(),
            'recordedValue' => $reports->recordedSalesValue($start, $end),
            'topProducts' => $reports->topProducts($start, $end),
            'reconciliation' => $reports->reconciliation(),
            'start' => $start,
            'end' => $end,
        ]);
    }

    public function inventoryCsv(QueryRequest $request, ReportQuery $reports): StreamedResponse
    {
        Gate::authorize('export-inventory');
        $rows = $reports->inventory($request->input('state', 'active'));

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['ID', 'SKU', 'Product', 'Category', 'Stock', 'Reorder level', 'Archived at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    (string) $row->id,
                    $this->csvText($row->sku),
                    $this->csvText($row->name),
                    $this->csvText($row->category),
                    (string) $row->stock_on_hand,
                    (string) $row->reorder_level,
                    $row->archived_at ?? '',
                ]);
            }
            fclose($out);
        }, 'crm-inventory.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvText(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) ? "'".$value : $value;
    }
}
