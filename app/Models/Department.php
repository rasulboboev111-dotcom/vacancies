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
     * The employee that manages this department (the API "managerId").
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

        // A cycle forms only if the chosen parent already sits below this
        // department. The recursive CTE is built without global scopes, so the
        // walk passes through soft-deleted nodes — a cycle routed through an
        // archived department is still caught.
        return $this->descendants()->whereKey($newParentId)->exists();
    }

    /**
     * Order departments the way the source API returns them: by the captured
     * sort_order first, then natural code, then name as a final tiebreaker.
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
