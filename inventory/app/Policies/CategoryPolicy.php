<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

final class CategoryPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function update(User $user, Category $category): bool
    {
        return $this->create($user);
    }

    public function archive(User $user, Category $category): bool
    {
        return $this->create($user);
    }
}
