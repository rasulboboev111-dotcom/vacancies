<?php

namespace App\Models;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\OrgStatus;
use App\Models\Concerns\BranchScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
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
        // Идентификатор человека из внешней системы, заполняется импортёром
        // оргструктуры (ImportOrgStructure: $e['personId']); это не локальный внешний ключ.
        'person_id' => 'integer',
        'category' => Category::class,
        'sort_order' => 'integer',
        'nationality_id' => 'integer',
        'education_id' => 'integer',
        'specialty_id' => 'integer',
        'birth_place_id' => 'integer',
        'employment_type' => EmploymentType::class,
        'gender' => Gender::class,
        // HR-статус из внешней системы, зеркалируется импортёром оргструктуры. Это НЕ
        // признак работает/уволен — им служит `dismissal_date` (см. scopeActive).
        'status' => OrgStatus::class,
        'hire_date' => 'date',
        'dismissal_date' => 'date',
        'birth_date' => 'date',
        'passport_start_date' => 'date',
        'passport_end_date' => 'date',
        'employment_start_date' => 'date',
    ];

    /**
     * Дублирует значение по умолчанию из БД, чтобы только что созданная
     * (несохранённая) модель показывала тот же тип занятости, что записала бы база.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'employment_type' => 'штатный',
    ];

    /**
     * Справочные связи за добавляемыми (appended) аксессорами имён всегда
     * сериализуются, поэтому загружаем их везде, чтобы избежать N+1 запросов по строкам.
     *
     * @var list<string>
     */
    protected $with = ['nationalityRef', 'educationRef', 'specialtyRef', 'birthPlaceRef'];

    protected $appends = ['age', 'gender_label', 'employment_type_label', 'nationality', 'education', 'specialty', 'birth_place'];

    /**
     * Возраст сотрудника, вычисленный по дате рождения.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Человекочитаемая метка пола (например, "Мужской") для фронтенда,
     * выводится из канонического значения enum Gender, хранящегося в колонке.
     */
    public function getGenderLabelAttribute(): ?string
    {
        return $this->gender?->label();
    }

    /**
     * Является ли сотрудник руководителем (роҳбар). Определяется тем, что
     * категория сотрудника — "Роҳбарият" (категория руководства), заданная в форме
     * сотрудника, а не тем, возглавляет ли он отдел. Требует загруженной
     * колонки `category`.
     */
    public function getIsManagerAttribute(): bool
    {
        return $this->category === Category::MANAGEMENT;
    }

    /**
     * Только работающие сотрудники (без даты увольнения).
     *
     * `dismissal_date` — единственный источник истины для различия
     * работает/уволен во всём приложении. Колонка `status` (OrgStatus: Active/Inactive) —
     * это HR-статус, зеркалируемый из внешней системы импортёром оргструктуры, и он
     * НЕ используется для этого различия — не стройте на нём логику работает/уволен.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('dismissal_date');
    }

    /**
     * Только уволенные/архивные сотрудники.
     */
    public function scopeDismissed($query)
    {
        return $query->whereNotNull('dismissal_date');
    }

    /**
     * Псевдополнотекстовый поиск по имени, названию должности и ИНН.
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Отдел (из нового дерева оргструктуры), к которому относится сотрудник.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Человекочитаемая метка типа занятости (например, "Штатӣ") для фронтенда,
     * выводится из канонического enum EmploymentType, хранящегося в колонке.
     */
    public function getEmploymentTypeLabelAttribute(): ?string
    {
        return EmploymentType::tryFrom($this->attributes['employment_type'] ?? '')?->label();
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Справочные связи для нормализованных свободно-текстовых атрибутов.
     * Имеют суффикс "Ref", чтобы голые имена атрибутов
     * (nationality, education, specialty, birth_place) могли отдавать
     * разрешённое строковое значение через аксессоры для совместимости с фронтендом.
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
     * Строковые аксессоры, сохраняющие контракт с фронтендом: employee.nationality,
     * employee.education, employee.specialty и employee.birth_place
     * сериализуются как обычные строки (название из справочника), а не как объекты.
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
     * Непосредственный руководитель сотрудника.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * История ротаций сотрудника.
     */
    public function rotations(): HasMany
    {
        return $this->hasMany(Rotation::class)->orderBy('rotation_date', 'desc');
    }
}
