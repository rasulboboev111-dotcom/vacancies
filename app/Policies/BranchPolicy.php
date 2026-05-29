<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('view branches');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('view branches');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create branches');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('edit branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('delete branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }
}
