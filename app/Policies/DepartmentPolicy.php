<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasPermissionTo('view departments');
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null
            && $user->hasPermissionTo('view departments')
            && $department->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create departments');
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('edit departments')) {
            return false;
        }

        return $department->branch_id === $user->branch_id;
    }

    public function delete(User $user, Department $department): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('delete departments')) {
            return false;
        }

        return $department->branch_id === $user->branch_id;
    }
}
