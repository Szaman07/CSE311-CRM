<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['full_name', 'email', 'phone'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'archived_at' => 'datetime'];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
