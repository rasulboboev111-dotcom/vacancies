<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('view employees');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('view employees');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create employees');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('edit employees')) {
            return false;
        }

        return $employee->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('delete employees')) {
            return false;
        }

        return $employee->branch_id === $user->branch_id;
    }
}
