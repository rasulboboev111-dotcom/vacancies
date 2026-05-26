<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'category_id',
                'type_id',
                'full_name',
                'gender',
                'position_id',
                'structure_id',
                'manager_id',
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
                'employment_start_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'branch_id',
        'category_id',
        'type_id',
        'full_name',
        'gender',
        'position_id',
        'structure_id',
        'manager_id',
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
        'employment_start_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'dismissal_date' => 'date',
        'birth_date' => 'date',
        'passport_start_date' => 'date',
        'passport_end_date' => 'date',
        'employment_start_date' => 'date',
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
     * Get the category that the employee belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the employment type that the employee belongs to.
     */
    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class, 'type_id');
    }

    /**
     * Get the position that the employee belongs to.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the structure that the employee belongs to.
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /**
     * Get the direct manager of the employee.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Get subordinates of the employee.
     */
    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Get rotations history of the employee.
     */
    public function rotations()
    {
        return $this->hasMany(Rotation::class)->orderBy('rotation_date', 'desc');
    }
}
