<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        foreach (['manage-catalog', 'manage-stock', 'cancel-sale', 'archive-customer', 'export-inventory'] as $ability) {
            Gate::define($ability, fn (User $user): bool => $user->is_active && $user->isManager());
        }
    }
}
