<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

/**
 * Использует Spatie checkPermissionTo (не hasPermissionTo): он не бросает
 * PermissionDoesNotExist, а возвращает false, если строки права нет в БД —
 * несидированная/частично смигрированная таблица прав не роняет страницы 500-ой.
 */
class ApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('view applications');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Application $application): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null
            && $user->checkPermissionTo('view applications')
            && $application->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('create applications');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Application $application): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('edit applications')) {
            return false;
        }

        return $application->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Application $application): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('delete applications')) {
            return false;
        }

        return $application->branch_id === $user->branch_id;
    }
}
