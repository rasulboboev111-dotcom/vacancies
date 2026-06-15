<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    /**
     * Ограничивает запрос тем, что разрешено видеть данному пользователю (см. BranchScope).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeViewableBy($query, User $user)
    {
        return BranchScope::apply($query, $user, $this->branchColumn());
    }

    /**
     * Колонка, связывающая модель с филиалом. По умолчанию — внешний ключ
     * branch_id; модель Branch переопределяет её собственным первичным ключом.
     */
    protected function branchColumn(): string
    {
        return 'branch_id';
    }
}
