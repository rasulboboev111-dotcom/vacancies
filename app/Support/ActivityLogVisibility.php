<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Правило видимости журнала действий по филиалу. Вынесено из модели Employee:
 * знание о том, какие сущности логируются и как они привязаны к филиалу — это
 * ответственность аудита, а не модели сотрудника. Используется списком журнала
 * и дашбордом, чтобы правило жило ровно в одном месте.
 */
class ActivityLogVisibility
{
    /**
     * Админы видят всё; пользователь филиала — только субъекты своего филиала;
     * не-админ без филиала — ничего.
     *
     * @param  Builder<Activity>  $query
     */
    public static function restrictTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->branch_id === null) {
            $query->whereRaw('1=0');

            return;
        }

        self::scopeToBranch($query, $user->branch_id);
    }

    /**
     * Ограничивает запрос субъектами филиала: его сотрудники, вакансии, отделы,
     * заявки, ротации (через сотрудника) или сам филиал. Субъекты без привязки к
     * филиалу (должности, пользователи) исключаются.
     *
     * @param  Builder<Activity>  $query
     */
    private static function scopeToBranch(Builder $query, int $branchId): void
    {
        $query->where(function ($outer) use ($branchId) {
            $outer->where(function ($q) use ($branchId) {
                $q->where('subject_type', Employee::class)
                    ->whereIn('subject_id', Employee::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Vacancy::class)
                    ->whereIn('subject_id', Vacancy::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Department::class)
                    ->whereIn('subject_id', Department::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Application::class)
                    ->whereIn('subject_id', Application::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                // Ротации не имеют своего branch_id — привязываем через корманда.
                $q->where('subject_type', Rotation::class)
                    ->whereIn('subject_id', Rotation::whereIn(
                        'employee_id',
                        Employee::withTrashed()->where('branch_id', $branchId)->select('id')
                    )->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Branch::class)->where('subject_id', $branchId);
            });
        });
    }
}
