<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->hasPermissionTo('view vacancies');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vacancy $vacancy): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if (!$user->hasPermissionTo('view vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->branch_id !== null && $user->hasPermissionTo('create vacancies');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vacancy $vacancy): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('edit vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vacancy $vacancy): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        if ($user->branch_id === null || !$user->hasPermissionTo('delete vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }
}
