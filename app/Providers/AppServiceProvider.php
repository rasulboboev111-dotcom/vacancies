<?php

namespace App\Providers;

use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URL generation in production so links, redirects and
        // assets are never emitted over plain HTTP (avoids mixed-content and
        // protocol-downgrade exposure). Local/testing stay on http.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        Gate::define('view-audit-logs', function ($user) {
            return $user->hasPermissionTo('view audit logs') || $user->isAdmin();
        });

        // Branch scope for raw query-builder aggregations (e.g. the dashboard
        // stats run on DB::table to skip Eloquent casts/appends). Eloquent
        // models use the BranchScoped trait's viewableBy() scope instead.
        QueryBuilder::macro('viewableByBranch', function (User $user, string $column = 'branch_id') {
            /** @var QueryBuilder $this */
            return BranchScope::apply($this, $user, $column);
        });
    }
}
