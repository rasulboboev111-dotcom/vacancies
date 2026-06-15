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
     * Отображаемое имя вакансии в списках и журналах аудита — название должности,
     * после того как дублирующий свободно-текстовый заголовок был убран.
     */
    public function displayName(): string
    {
        // Если связь position уже загружена (eager load) — берём имя из неё без
        // лишнего запроса (иначе был бы N+1 в списках); getRelation() возвращает
        // mixed, что допускает nullsafe. Без загрузки делаем точечный запрос
        // только за именем. value() даёт null, когда должности нет (position_id
        // допускает null).
        $name = $this->relationLoaded('position')
            ? $this->getRelation('position')?->name
            : $this->position()->value('name');

        return $name ?? 'Вакансия №'.$this->id;
    }
}
