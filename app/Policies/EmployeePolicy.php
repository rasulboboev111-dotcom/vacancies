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
        return $user->hasPermissionTo('view employees') || $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('view employees') && !$user->hasRole('Admin')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return $employee->branch_id === $user->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create employees') || $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('edit employees') && !$user->hasRole('Admin')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return $employee->branch_id === $user->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('delete employees') && !$user->hasRole('Admin')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return $employee->branch_id === $user->branch_id;
        }

        return true;
    }
}
