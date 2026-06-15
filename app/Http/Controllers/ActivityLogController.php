<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogPresenter;
use App\Support\ActivityLogVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogController extends Controller
{
    public function index(Request $request, ActivityLogPresenter $presenter): Response
    {
        Gate::authorize('view-audit-logs');

        $base = Activity::with('causer')->latest()->latest('id');

        // Пользователи филиала (не админы) всегда видят только записи журнала
        // по субъектам своего филиала — эшелонированная защита, независимая от
        // того, у кого есть право 'view audit logs'.
        $user = $request->user();
        ActivityLogVisibility::restrictTo($base, $user);

        $logs = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::partial('search', 'description'),
                AllowedFilter::exact('event'),
            ])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Activity $log) => $presenter->present($log));

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->input('filter', []),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Полностью очищает журнал аудита. Только для админов: очистка журнала —
     * разрушительное, необратимое действие, поэтому ограничено строже, чем
     * простой просмотр журнала (доступный пользователям филиала с правом).
     */
    public function clear(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        Activity::query()->delete();

        return back()->with('success', 'Сабти амалҳо пурра тоза карда шуд.');
    }
}
