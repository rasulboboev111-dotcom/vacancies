<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\OrgStatus;
use App\Models\Concerns\BranchScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use BranchScoped, HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'department_id',
                'category',
                'employment_type',
                'full_name',
                'gender',
                'position_id',
                'manager_id',
                'hire_date',
                'dismissal_date',
                'dismissal_reason',
                'birth_date',
                'nationality_id',
                'passport_number',
                'passport_start_date',
                'passport_end_date',
                'passport_issued_by',
                'inn',
                'sin',
                'address',
                'phone_number',
                'email',
                'birth_place_id',
                'education_id',
                'specialty_id',
                'employment_start_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'external_id',
        'person_id',
        'email',
        'status',
        'sort_order',
        'branch_id',
        'department_id',
        'category',
        'employment_type',
        'full_name',
        'gender',
        'position_id',
        'manager_id',
        'hire_date',
        'dismissal_date',
        'dismissal_reason',
        'birth_date',
        'nationality_id',
        'passport_number',
        'passport_start_date',
        'passport_end_date',
        'passport_issued_by',
        'inn',
        'sin',
        'address',
        'phone_number',
        'birth_place_id',
        'education_id',
        'specialty_id',
        'employment_start_date',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'department_id' => 'integer',
        'position_id' => 'integer',
        'manager_id' => 'integer',
        'person_id' => 'integer',
        'category' => Category::class,
        'sort_order' => 'integer',
        'nationality_id' => 'integer',
        'education_id' => 'integer',
        'specialty_id' => 'integer',
        'birth_place_id' => 'integer',
        'employment_type' => EmploymentType::class,
        'gender' => Gender::class,
        'status' => OrgStatus::class,
        'hire_date' => 'date',
        'dismissal_date' => 'date',
        'birth_date' => 'date',
        'passport_start_date' => 'date',
        'passport_end_date' => 'date',
        'employment_start_date' => 'date',
    ];

    /**
     * Mirror the DB-side default so a freshly instantiated (unsaved) model
     * reports the same employment type the database would store.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'employment_type' => 'штатный',
    ];

    /**
     * The lookup relations behind the appended name accessors are always
     * serialized, so eager-load them everywhere to avoid per-row N+1 queries.
     *
     * @var list<string>
     */
    protected $with = ['nationalityRef', 'educationRef', 'specialtyRef', 'birthPlaceRef'];

    protected $appends = ['age', 'gender_label', 'employment_type_label', 'nationality', 'education', 'specialty', 'birth_place'];

    /**
     * Get employee's age based on birth date.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Human-readable gender label (e.g. "Мужской") for the frontend,
     * derived from the canonical Gender enum value stored in the column.
     */
    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender?->label();
    }

    /**
     * Whether the employee is a manager (роҳбар). Determined by the employee's
     * category being "Роҳбарият" (the management category) set on the employee
     * form — not by whether they head a department. Requires the `category`
     * column to be loaded.
     */
    public function getIsManagerAttribute(): bool
    {
        return $this->category === Category::MANAGEMENT;
    }

    /**
     * Only employees that are still employed (no dismissal date).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('dismissal_date');
    }

    /**
     * Only dismissed/archived employees.
     */
    public function scopeDismissed($query)
    {
        return $query->whereNotNull('dismissal_date');
    }

    /**
     * Full-text-ish search across name, position name and INN.
     */
    public function scopeSearch($query, ?string $term)
    {
        if ($term === null || $term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhereHas('position', fn ($posQ) => $posQ->where('name', 'like', "%{$term}%"))
                ->orWhere('inn', 'like', "%{$term}%");
        });
    }

    /**
     * Restrict an activity-log query to subjects that belong to the given
     * branch (its employees, vacancies, departments, or the branch itself).
     * Subjects without a branch dimension (e.g. positions, users) are excluded.
     *
     * Shared by the dashboard and the activity-log listing so both apply the
     * exact same branch filter.
     *
     * @param  Builder<Activity>  $query
     */
    public static function filterActivitiesByBranch($query, int $branchId): void
    {
        $query->where(function ($outer) use ($branchId) {
            $outer->where(function ($q) use ($branchId) {
                $q->where('subject_type', Employee::class)
                    ->whereIn('subject_id', Employee::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Vacancy::class)
                    ->whereIn('subject_id', Vacancy::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Department::class)
                    ->whereIn('subject_id', Department::withTrashed()->where('branch_id', $branchId)->select('id'));
            })->orWhere(function ($q) use ($branchId) {
                $q->where('subject_type', Branch::class)->where('subject_id', $branchId);
            });
        });
    }

    /**
     * Apply the full activity-log visibility rule for a user: admins see
     * everything, a branch user sees only their branch's subjects, and a
     * branch-less non-admin sees nothing. Shared by the dashboard and the
     * activity-log listing so the rule lives in exactly one place.
     *
     * @param  Builder<Activity>  $query
     */
    public static function restrictActivitiesTo($query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->branch_id === null) {
            $query->whereRaw('1=0');

            return;
        }

        self::filterActivitiesByBranch($query, $user->branch_id);
    }

    /**
     * Get the branch that the employee belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the department (new org tree) that the employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Human-readable employment-type label (e.g. "Штатӣ") for the frontend,
     * derived from the canonical EmploymentType enum stored in the column.
     */
    public function getEmploymentTypeLabelAttribute(): ?string
    {
        return EmploymentType::tryFrom($this->attributes['employment_type'] ?? '')?->label();
    }

    /**
     * Get the position that the employee belongs to.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Lookup relationships for the normalized free-text attributes.
     * They are suffixed with "Ref" so the bare attribute names
     * (nationality, education, specialty, birth_place) can expose the
     * resolved string value via accessors for frontend compatibility.
     */
    public function nationalityRef(): BelongsTo
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function educationRef(): BelongsTo
    {
        return $this->belongsTo(Education::class, 'education_id');
    }

    public function specialtyRef(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function birthPlaceRef(): BelongsTo
    {
        return $this->belongsTo(BirthPlace::class, 'birth_place_id');
    }

    /**
     * String accessors that keep the frontend contract: employee.nationality,
     * employee.education, employee.specialty and employee.birth_place are
     * serialized as plain strings (the lookup name) rather than objects.
     */
    public function getNationalityAttribute(): ?string
    {
        return $this->nationalityRef?->name;
    }

    public function getEducationAttribute(): ?string
    {
        return $this->educationRef?->name;
    }

    public function getSpecialtyAttribute(): ?string
    {
        return $this->specialtyRef?->name;
    }

    public function getBirthPlaceAttribute(): ?string
    {
        return $this->birthPlaceRef?->name;
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
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Departments this employee heads. Backs the derived is_manager flag.
     */
    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    /**
     * Get rotations history of the employee.
     */
    public function rotations(): HasMany
    {
        return $this->hasMany(Rotation::class)->orderBy('rotation_date', 'desc');
    }
}
