<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'category_id' => (string) $this->category_id,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'sku' => $this->sku,
            'name' => $this->name,
            'unit_price' => (string) $this->unit_price,
            'currency' => 'BDT',
            'stock_on_hand' => $this->stock_on_hand,
            'reorder_level' => $this->reorder_level,
            'version' => (string) $this->version,
            'archived_at' => $this->archived_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
