<?php

namespace App\Models;

use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Application extends Model implements HasMedia
{
    use BranchScoped;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'external_id', 'branch_id', 'vacancy_id', 'name', 'email', 'phone',
        'vacancy_title', 'source', 'summary', 'survey',
        'source_created_at',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'vacancy_id' => 'integer',
        'survey' => 'array',
        'source_created_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('resumes')
            ->singleFile()
            ->useDisk(config('intake.disk'));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('vacancy_title', 'like', $like);
        });
    }
}
