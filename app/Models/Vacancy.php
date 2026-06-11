<?php

namespace App\Models;

use App\Enums\Education;
use App\Enums\Experience;
use App\Enums\OpeningReason;
use App\Enums\Probation;
use App\Enums\ScheduleType;
use App\Enums\VacancyEmploymentType;
use App\Enums\VacancyPriority;
use App\Enums\VacancyStatus;
use App\Enums\WorkFormat;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vacancy extends Model
{
    use BranchScoped, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'department_id',
        'position_id',
        'created_by',
        'location',
        'openings',
        'supervisor',
        'education',
        'experience',
        'skills',
        'requirements',
        'responsibilities',
        'employment_type',
        'schedule_type',
        'schedule_other',
        'work_format',
        'salary',
        'probation',
        'probation_other',
        'opening_reason',
        'priority',
        'status',
        'opened_at',
        'deadline',
        'closed_at',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'department_id' => 'integer',
        'position_id' => 'integer',
        'created_by' => 'integer',
        'openings' => 'integer',
        'salary' => 'integer',
        'education' => Education::class,
        'experience' => Experience::class,
        'employment_type' => VacancyEmploymentType::class,
        'schedule_type' => ScheduleType::class,
        'work_format' => WorkFormat::class,
        'probation' => Probation::class,
        'opening_reason' => OpeningReason::class,
        'priority' => VacancyPriority::class,
        'status' => VacancyStatus::class,
        'opened_at' => 'date',
        'deadline' => 'date',
        'closed_at' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'department_id',
                'position_id',
                'location',
                'openings',
                'supervisor',
                'education',
                'experience',
                'skills',
                'requirements',
                'responsibilities',
                'employment_type',
                'schedule_type',
                'schedule_other',
                'work_format',
                'salary',
                'probation',
                'probation_other',
                'opening_reason',
                'priority',
                'status',
                'opened_at',
                'deadline',
                'closed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<VacancyLanguage, $this>
     */
    public function languages(): HasMany
    {
        return $this->hasMany(VacancyLanguage::class);
    }

    /**
     * The vacancy's display name on lists and audit logs — the position name,
     * now that the duplicate free-text title is gone.
     */
    public function displayName(): string
    {
        return $this->position->name ?? 'Вакансия №'.$this->id;
    }
}
