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
    public function boot(): void
    {
        // Принудительно генерируем URL по HTTPS в production, чтобы ссылки,
        // редиректы и ассеты никогда не отдавались по обычному HTTP (избегаем
        // mixed-content и понижения протокола). Local/testing остаются на http.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        Gate::define('view-audit-logs', function ($user) {
            return $user->hasPermissionTo('view audit logs') || $user->isAdmin();
        });

        // Ограничение по филиалу для агрегаций на сыром query-builder (например,
        // статистика дашборда работает через DB::table, минуя касты/appends
        // Eloquent). Модели Eloquent вместо этого используют скоуп viewableBy()
        // трейта BranchScoped.
        QueryBuilder::macro('viewableByBranch', function (User $user, string $column = 'branch_id') {
            /** @var QueryBuilder $this */
            return BranchScope::apply($this, $user, $column);
        });
    }
}
