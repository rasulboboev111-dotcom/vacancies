<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('view employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null
            && $user->hasPermissionTo('view employees')
            && $employee->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('edit employees')) {
            return false;
        }

        return $employee->branch_id === $user->branch_id;
    }

    public function delete(User $user, Employee $employee): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('delete employees')) {
            return false;
        }

        return $employee->branch_id === $user->branch_id;
    }
}
