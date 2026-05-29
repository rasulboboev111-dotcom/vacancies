<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vacancy extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'branch_id',
        'department_id',
        'position_id',
        'structure_id',
        'created_by',
        'title',
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
        'structure_id' => 'integer',
        'created_by' => 'integer',
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
                'structure_id',
                'title',
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
        return $this->status === self::STATUS_OPEN;
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

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
