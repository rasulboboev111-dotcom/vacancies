<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users (Admin, HR Manager, Branch Manager, Viewer) can view positions
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Position $position): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Admin and HR Manager can create positions
        return $user->hasRole('Admin') || $user->hasRole('HR Manager');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Position $position): bool
    {
        // Only Admin and HR Manager can update positions
        return $user->hasRole('Admin') || $user->hasRole('HR Manager');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Position $position): bool
    {
        // Only Admin can delete positions (similar to branches policy)
        return $user->hasRole('Admin');
    }
}
