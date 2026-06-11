<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null;
    }

    public function view(User $user, Position $position): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Position $position): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->isAdmin();
    }
}
