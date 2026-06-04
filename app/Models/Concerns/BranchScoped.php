<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    /**
     * Restrict the query to what the given user may see (see BranchScope).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeViewableBy($query, User $user)
    {
        return BranchScope::apply($query, $user, $this->branchColumn());
    }

    /**
     * The column that ties this model to a branch. Defaults to the branch
     * foreign key; the Branch model overrides this with its own primary key.
     */
    protected function branchColumn(): string
    {
        return 'branch_id';
    }
}
