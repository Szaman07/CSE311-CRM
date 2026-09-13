<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'product_id' => (string) $this->product_id,
            'sale_item_id' => $this->sale_item_id === null ? null : (string) $this->sale_item_id,
            'sale_id' => $this->whenLoaded('saleItem', fn () => $this->saleItem === null ? null : (string) $this->saleItem->sale_id),
            'quantity_delta' => $this->quantity_delta,
            'resulting_stock' => $this->resulting_stock,
            'reason' => $this->reason,
            'actor' => $this->whenLoaded('actor', fn () => ['id' => (string) $this->actor->id, 'name' => $this->actor->name]),
            'note' => $this->note,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
