<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->hasPermissionTo('view departments');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->hasPermissionTo('view departments');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create departments');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('edit departments')) {
            return false;
        }

        return $department->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('delete departments')) {
            return false;
        }

        return $department->branch_id === $user->branch_id;
    }
}
