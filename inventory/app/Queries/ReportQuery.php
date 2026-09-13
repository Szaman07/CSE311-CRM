<?php

namespace App\Queries;

use App\Models\Customer;
use App\Support\LocalDateRange;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReportQuery
{
    public function inventory(?string $state = 'active'): Collection
    {
        return DB::table('products as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->when($state === 'active', fn (Builder $q) => $q->whereNull('p.archived_at'))
            ->when($state === 'archived', fn (Builder $q) => $q->whereNotNull('p.archived_at'))
            ->select('p.id', 'p.sku', 'p.name', 'c.name as category', 'p.stock_on_hand', 'p.reorder_level', 'p.archived_at')
            ->orderBy('p.name')->orderBy('p.id')->get();
    }

    public function lowStock(): Collection
    {
        return DB::table('products')->whereNull('archived_at')
            ->whereColumn('stock_on_hand', '<=', 'reorder_level')
            ->select('id', 'sku', 'name', 'stock_on_hand', 'reorder_level')
            ->orderBy('stock_on_hand')->orderBy('id')->get();
    }

    public function customerHistory(Customer $customer, int $perPage = 20)
    {
        return DB::table('sales as s')->join('sale_totals as t', 't.sale_id', '=', 's.id')
            ->where('s.customer_id', $customer->id)
            ->select('s.id', 's.status', 's.created_at', 's.cancelled_at', 't.total')
            ->orderByDesc('s.created_at')->orderByDesc('s.id')->paginate(min(100, max(1, $perPage)));
    }

    public function recordedSalesValue(string $start, string $end): string
    {
        [$from, $until] = LocalDateRange::toUtc($start, $end);
        $value = DB::table('sales as s')->join('sale_items as i', 'i.sale_id', '=', 's.id')
            ->where('s.status', 'completed')->where('s.created_at', '>=', $from)->where('s.created_at', '<', $until)
            ->selectRaw('CAST(COALESCE(SUM(i.quantity * i.unit_price), 0) AS DECIMAL(22,2)) AS total')->value('total');

        return (string) $value;
    }

    public function topProducts(string $start, string $end, int $limit = 10): Collection
    {
        [$from, $until] = LocalDateRange::toUtc($start, $end);

        return DB::table('sales as s')->join('sale_items as i', 'i.sale_id', '=', 's.id')
            ->where('s.status', 'completed')->where('s.created_at', '>=', $from)->where('s.created_at', '<', $until)
            ->groupBy('i.product_id', 'i.product_sku', 'i.product_name')
            ->select('i.product_id', 'i.product_sku', 'i.product_name')
            ->selectRaw('SUM(i.quantity) AS units')
            ->selectRaw('CAST(SUM(i.quantity*i.unit_price) AS DECIMAL(22,2)) AS recorded_value')
            ->orderByDesc('units')->orderBy('i.product_id')->limit($limit)->get();
    }

    public function reconciliation(): array
    {
        return DB::transaction(function (): array {
            $balances = DB::table('inventory_reconciliation')->orderBy('product_id')->get();
            $saleLinks = DB::table('sale_items as i')
                ->join('sales as s', 's.id', '=', 'i.sale_id')
                ->leftJoin('stock_movements as d', function ($join): void {
                    $join->on('d.sale_item_id', '=', 'i.id')->where('d.reason', '=', 'sale');
                })
                ->leftJoin('stock_movements as r', function ($join): void {
                    $join->on('r.sale_item_id', '=', 'i.id')->where('r.reason', '=', 'cancellation');
                })
                ->groupBy('i.id', 'i.product_id', 'i.quantity', 's.status')
                ->select('i.id as sale_item_id', 'i.product_id', 'i.quantity', 's.status')
                ->selectRaw('COUNT(DISTINCT d.id) AS deduction_count')
                ->selectRaw('MIN(d.quantity_delta) AS deduction_delta')
                ->selectRaw('COUNT(DISTINCT r.id) AS reversal_count')
                ->selectRaw('MIN(r.quantity_delta) AS reversal_delta')
                ->havingRaw('deduction_count <> 1 OR deduction_delta <> -i.quantity OR (s.status = ? AND (reversal_count <> 1 OR reversal_delta <> i.quantity)) OR (s.status = ? AND reversal_count <> 0)', ['cancelled', 'completed'])
                ->orderBy('i.id')->get();
            $emptySales = DB::table('sales as s')->leftJoin('sale_items as i', 'i.sale_id', '=', 's.id')
                ->groupBy('s.id')->havingRaw('COUNT(i.id)=0')->pluck('s.id');

            return ['balances' => $balances, 'sale_link_issues' => $saleLinks, 'empty_sale_ids' => $emptySales];
        }, 3);
    }
}
