<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Использует Spatie checkPermissionTo (не hasPermissionTo): он не бросает
 * PermissionDoesNotExist, а возвращает false, если строки права нет в БД —
 * несидированная/частично смигрированная таблица прав не роняет страницы 500-ой.
 */
class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->checkPermissionTo('view departments');
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null
            && $user->checkPermissionTo('view departments')
            && $department->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('create departments');
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('edit departments')) {
            return false;
        }

        return $department->branch_id === $user->branch_id;
    }

    public function delete(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('delete departments')) {
            return false;
        }

        return $department->branch_id === $user->branch_id;
    }
}
