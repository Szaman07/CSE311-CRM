<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

final class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function update(User $user, Product $product): bool
    {
        return $this->create($user);
    }

    public function archive(User $user, Product $product): bool
    {
        return $this->create($user);
    }

    public function adjust(User $user, Product $product): bool
    {
        return $this->create($user);
    }
}
