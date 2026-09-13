<?php

namespace App\Http\Resources;

use App\Support\Canonical;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = 0;
        if ($this->relationLoaded('items')) {
            foreach ($this->items as $item) {
                $total += Canonical::cents((string) $item->unit_price) * $item->quantity;
            }
        }

        return [
            'id' => (string) $this->id,
            'receipt_number' => $this->receipt_number,
            'customer_id' => $this->customer_id === null ? null : (string) $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? new CustomerResource($this->customer) : null),
            'created_by' => (string) $this->created_by,
            'status' => $this->status,
            'total' => Canonical::formatCents($total),
            'currency' => 'BDT',
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => (string) $i->id, 'product_id' => (string) $i->product_id, 'quantity' => $i->quantity,
                'unit_price' => (string) $i->unit_price, 'product_name' => $i->product_name, 'product_sku' => $i->product_sku,
                'line_total' => Canonical::formatCents(Canonical::cents((string) $i->unit_price) * $i->quantity),
            ])),
            'cancelled_by' => $this->cancelled_by === null ? null : (string) $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
