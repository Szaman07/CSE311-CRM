<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

final class SalePolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $user->is_active && $user->isManager();
    }
}
