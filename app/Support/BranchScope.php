<?php

namespace App\Support;

use App\Models\User;

class BranchScope
{
    /**
     * Limit a query to the branch the given user may see. This is the single
     * source of truth behind the BranchScoped trait (Eloquent) and the
     * `viewableByBranch` query macro (raw queries):
     *
     * - admins see every branch (the query is left untouched);
     * - a non-admin sees only their own branch;
     * - a non-admin without a branch sees nothing.
     *
     * $column is the branch foreign key ('branch_id') for most models, or the
     * primary key ('id') when scoping the Branch model itself.
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
