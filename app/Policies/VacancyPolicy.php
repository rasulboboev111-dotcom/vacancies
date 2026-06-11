<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasPermissionTo('view vacancies');
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermissionTo('view vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create vacancies');
    }

    public function update(User $user, Vacancy $vacancy): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('edit vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }

    public function delete(User $user, Vacancy $vacancy): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->hasPermissionTo('delete vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }
}
