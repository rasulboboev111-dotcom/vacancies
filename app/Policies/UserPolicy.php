<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Просмотр списка и создание пользователей доступны админам; редактирование
     * и удаление (и вся корзина) — только суперадмину.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Редактирование — только суперадмин, и сам аккаунт суперадмина через
     * управление пользователями неприкосновенен: иначе форма (где роли только
     * Admin/User) понизила бы его и заблокировала все суперадмин-действия.
     * Свой профиль суперадмин правит через страницу «Профил».
     */
    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && ! $model->isSuperAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && ! $model->isSuperAdmin();
    }
}
