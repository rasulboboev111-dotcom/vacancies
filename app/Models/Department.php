<?php

namespace App\Models;

use App\Enums\OrgStatus;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Department extends Model
{
    use BranchScoped, HasFactory, HasRecursiveRelationships, LogsActivity, SoftDeletes;

    protected $fillable = [
        'external_id',
        'branch_id',
        'parent_id',
        'name',
        'short_name',
        'code',
        'status',
        'sort_order',
        'manager_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'branch_id' => 'integer',
            'parent_id' => 'integer',
            'manager_id' => 'integer',
            'sort_order' => 'integer',
            'status' => OrgStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'parent_id',
                'name',
                'short_name',
                'code',
                'status',
                'manager_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Сотрудник, руководящий этим отделом (поле "managerId" из API).
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function wouldCreateCycle(?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $this->id) {
            return true;
        }

        // Цикл возникает, только если выбранный родитель уже находится ниже
        // этого отдела. Рекурсивный CTE строится без глобальных scope, поэтому
        // обход проходит и через soft-deleted узлы — цикл через
        // архивный отдел тоже будет пойман.
        return $this->descendants()->whereKey($newParentId)->exists();
    }

    /**
     * Сортирует отделы так, как их возвращает исходный API: сначала по
     * сохранённому sort_order, затем по code, и по name как финальный критерий.
     *
     * @param  Collection<int, Department>  $departments
     * @return Collection<int, Department>
     */
    public static function sortBySource($departments)
    {
        return $departments
            ->sortBy([
                ['sort_order', 'asc'],
                ['code', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }
}
