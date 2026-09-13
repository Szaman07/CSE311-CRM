<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['category_id', 'sku', 'name', 'unit_price', 'reorder_level'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'stock_on_hand' => 'integer', 'reorder_level' => 'integer', 'version' => 'integer', 'archived_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
