<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

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
                'gender',
                'position',
                'structure',
                'direct_manager',
                'hire_date',
                'dismissal_date',
                'birth_date',
                'nationality',
                'passport_number',
                'passport_start_date',
                'passport_end_date',
                'passport_issued_by',
                'inn',
                'sin',
                'address',
                'phone_number',
                'birth_place',
                'education',
                'specialty',
                'total_experience',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'branch_id',
        'category',
        'type',
        'full_name',
        'gender',
        'position',
        'structure',
        'direct_manager',
        'hire_date',
        'dismissal_date',
        'birth_date',
        'nationality',
        'passport_number',
        'passport_start_date',
        'passport_end_date',
        'passport_issued_by',
        'inn',
        'sin',
        'address',
        'phone_number',
        'birth_place',
        'education',
        'specialty',
        'total_experience',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'dismissal_date' => 'date',
        'birth_date' => 'date',
        'passport_start_date' => 'date',
        'passport_end_date' => 'date',
    ];

    protected $appends = ['age'];

    /**
     * Get employee's age based on birth date.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Get the branch that the employee belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get rotations history of the employee.
     */
    public function rotations()
    {
        return $this->hasMany(Rotation::class)->orderBy('rotation_date', 'desc');
    }
}
