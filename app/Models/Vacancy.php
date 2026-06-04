<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'title',
        'openings',
        'employment_type',
        'requirements',
        'schedule',
        'salary',
        'description',
        'status',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'department_id' => 'integer',
        'position_id' => 'integer',
        'created_by' => 'integer',
        'openings' => 'integer',
        'employment_type' => EmploymentType::class,
        'status' => VacancyStatus::class,
        'opened_at' => 'date',
        'closed_at' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'department_id',
                'position_id',
                'title',
                'openings',
                'employment_type',
                'requirements',
                'schedule',
                'salary',
                'description',
                'status',
                'opened_at',
                'closed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isOpen(): bool
    {
        return $this->status === VacancyStatus::OPEN;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
