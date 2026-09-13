<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

trait GuardsDomain
{
    private function requireActive(User $actor): void
    {
        if (! $actor->is_active) {
            throw new AuthorizationException('This account is inactive.');
        }
    }

    private function requireManager(User $actor): void
    {
        $this->requireActive($actor);
        if (! $actor->isManager()) {
            throw new AuthorizationException('Manager access is required.');
        }
    }
}
