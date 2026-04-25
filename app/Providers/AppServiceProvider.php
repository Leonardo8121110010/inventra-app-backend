<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Integrate custom RBAC with Laravel's native authorization.
         * This allows @can('view-branches'), $user->can('view-branches'),
         * and Policy methods to work seamlessly with our custom permissions.
         */
        Gate::before(function ($user, $ability) {
            // If the ability matches a permission name, delegate to our RBAC
            if ($user->hasPermission($ability)) {
                return true;
            }
            // Return null to let other Gates/Policies evaluate
            return null;
        });
    }
}
