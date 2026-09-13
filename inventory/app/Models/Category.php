<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime', 'version' => 'integer'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
