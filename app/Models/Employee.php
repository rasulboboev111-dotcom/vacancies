<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'category',
                'type',
                'full_name',
                'position',
                'structure',
                'direct_manager',
                'hire_date',
                'dismissal_date',
                'passport_issued_by',
                'inn',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'branch_id',
        'category',
        'type',
        'full_name',
        'position',
        'structure',
        'direct_manager',
        'hire_date',
        'dismissal_date',
        'passport_issued_by',
        'inn',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'dismissal_date' => 'date',
    ];

    /**
     * Get the branch that the employee belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
