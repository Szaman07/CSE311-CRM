<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

final class CustomerPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->is_active;
    }

    public function archive(User $user, Customer $customer): bool
    {
        return $user->is_active && $user->isManager();
    }
}
