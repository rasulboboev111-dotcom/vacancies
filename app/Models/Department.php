<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'parent_id',
        'name',
        'code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'branch_id' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'parent_id',
                'name',
                'code',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function ancestors(): Collection
    {
        $ancestors = new Collection();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function isDescendantOf(int $id): bool
    {
        return $this->ancestors()->pluck('id')->contains($id);
    }

    public function wouldCreateCycle(?int $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $this->id) {
            return true;
        }

        $parent = static::query()->find($newParentId);

        return $parent?->isDescendantOf($this->id) ?? false;
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return list<array<string, mixed>>
     */
    public static function buildTree(Collection $departments, ?int $parentId = null): array
    {
        return $departments
            ->where('parent_id', $parentId)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (Department $department) use ($departments) {
                return [
                    'id' => $department->id,
                    'branch_id' => $department->branch_id,
                    'parent_id' => $department->parent_id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'children_count' => $departments->where('parent_id', $department->id)->count(),
                    'children' => static::buildTree($departments, $department->id),
                ];
            })
            ->all();
    }
}
