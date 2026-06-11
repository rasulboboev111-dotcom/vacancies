<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('view branches');
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('view branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create branches');
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('edit branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('delete branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }
}
