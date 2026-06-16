<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Использует Spatie checkPermissionTo (не hasPermissionTo): он не бросает
 * PermissionDoesNotExist, а возвращает false, если строки права нет в БД. Это
 * не даёт несидированной/частично смигрированной таблице прав ронять страницы
 * 500-ой (например, structure.index, который теперь вызывает viewAny).
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('view employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null
            && $user->checkPermissionTo('view employees')
            && $employee->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('create employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('edit employees')) {
            return false;
        }

        return $employee->branch_id === $user->branch_id;
    }

    public function delete(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('delete employees')) {
            return false;
        }

        return $employee->branch_id === $user->branch_id;
    }
}
