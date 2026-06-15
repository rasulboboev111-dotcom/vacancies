<?php

namespace App\Support;

use App\Models\User;

class BranchScope
{
    /**
     * Ограничивает запрос филиалом, который виден данному пользователю. Это
     * единственный источник истины за трейтом BranchScoped (Eloquent) и
     * макросом запросов `viewableByBranch` (сырые запросы):
     *
     * - админы видят все филиалы (запрос не трогается);
     * - не-админ видит только свой филиал;
     * - не-админ без филиала не видит ничего.
     *
     * $column — внешний ключ филиала ('branch_id') для большинства моделей или
     * первичный ключ ('id') при ограничении самой модели Branch.
     *
     * @template TQuery of \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public static function apply($query, User $user, string $column = 'branch_id')
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->branch_id === null) {
            return $query->whereRaw('1=0');
        }

        return $query->where($column, $user->branch_id);
    }
}
