<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * Использует Spatie checkPermissionTo (не hasPermissionTo): он не бросает
 * PermissionDoesNotExist, а возвращает false, если строки права нет в БД —
 * несидированная/частично смигрированная таблица прав не роняет страницы 500-ой.
 */
class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('view branches');
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('view branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('create branches');
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('edit branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('delete branches')) {
            return false;
        }

        return $branch->id === $user->branch_id;
    }
}
