<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacancy;

/**
 * Использует Spatie checkPermissionTo (не hasPermissionTo): он не бросает
 * PermissionDoesNotExist, а возвращает false, если строки права нет в БД —
 * несидированная/частично смигрированная таблица прав не роняет страницы 500-ой.
 */
class VacancyPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('view vacancies');
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->checkPermissionTo('view vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->branch_id !== null && $user->checkPermissionTo('create vacancies');
    }

    public function update(User $user, Vacancy $vacancy): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('edit vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }

    public function delete(User $user, Vacancy $vacancy): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->branch_id === null || ! $user->checkPermissionTo('delete vacancies')) {
            return false;
        }

        return $vacancy->branch_id === $user->branch_id;
    }
}
