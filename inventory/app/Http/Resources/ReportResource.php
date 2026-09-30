<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;

final class ReportResource
{
    public static function inventory(object $row): array
    {
        return [
            'id' => (string) $row->id, 'sku' => $row->sku, 'name' => $row->name,
            'category' => $row->category, 'stock_on_hand' => (int) $row->stock_on_hand,
            'reorder_level' => (int) $row->reorder_level,
            'archived_at' => $row->archived_at ? CarbonImmutable::parse($row->archived_at, 'UTC')->toIso8601String() : null,
        ];
    }

    public static function lowStock(object $row): array
    {
        return [
            'id' => (string) $row->id, 'sku' => $row->sku, 'name' => $row->name,
            'stock_on_hand' => (int) $row->stock_on_hand, 'reorder_level' => (int) $row->reorder_level,
        ];
    }

    public static function topProduct(object $row): array
    {
        return [
            'product_id' => (string) $row->product_id, 'product_sku' => $row->product_sku,
            'product_name' => $row->product_name, 'units' => (string) $row->units,
            'recorded_value' => (string) $row->recorded_value,
        ];
    }

    public static function reconciliation(array $report): array
    {
        return [
            'balances' => $report['balances']->map(fn ($row) => [
                'product_id' => (string) $row->product_id, 'stock_on_hand' => (int) $row->stock_on_hand,
                'ledger_stock' => (string) $row->ledger_stock, 'difference' => (string) $row->difference,
            ]),
            'sale_link_issues' => $report['sale_link_issues']->map(fn ($row) => [
                'sale_item_id' => (string) $row->sale_item_id, 'product_id' => (string) $row->product_id,
                'quantity' => (int) $row->quantity, 'status' => $row->status,
                'deduction_count' => (int) $row->deduction_count, 'deduction_delta' => $row->deduction_delta === null ? null : (int) $row->deduction_delta,
                'reversal_count' => (int) $row->reversal_count, 'reversal_delta' => $row->reversal_delta === null ? null : (int) $row->reversal_delta,
            ]),
            'empty_sale_ids' => $report['empty_sale_ids']->map(fn ($id) => (string) $id),
        ];
    }
}
