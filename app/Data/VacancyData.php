<?php

namespace App\Data;

use App\Models\Vacancy;
use App\Models\VacancyLanguage;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The shape of a vacancy row sent to the Vacancies/Index page. Keys mirror the
 * column/relation names the Vue components already read; enum columns travel
 * as value + human label («как в бланке»).
 */
#[TypeScript]
class VacancyData extends Data
{
    /**
     * @param  array<string, mixed>|null  $branch
     * @param  array<string, mixed>|null  $department
     * @param  array<string, mixed>|null  $position
     * @param  list<string>  $languages
     * @param  array<string, mixed>|null  $creator
     */
    public function __construct(
        public int $id,
        public ?int $branch_id,
        public ?array $branch,
        public ?int $department_id,
        public ?array $department,
        public ?int $position_id,
        public ?array $position,
        public ?string $location,
        public int $openings,
        public ?string $supervisor,
        public ?string $education,
        public ?string $education_label,
        public ?string $experience,
        public ?string $experience_label,
        public array $languages,
        public ?string $skills,
        public ?string $requirements,
        public ?string $responsibilities,
        public ?string $employment_type,
        public ?string $employment_type_label,
        public ?string $schedule_type,
        public ?string $schedule_type_label,
        public ?string $schedule_other,
        public ?string $work_format,
        public ?string $work_format_label,
        public ?int $salary,
        public ?string $probation,
        public ?string $probation_label,
        public ?string $probation_other,
        public ?string $opening_reason,
        public ?string $opening_reason_label,
        public ?string $priority,
        public ?string $priority_label,
        public ?string $status,
        public ?string $opened_at,
        public ?string $deadline,
        public ?string $closed_at,
        public ?array $creator,
    ) {}

    public static function fromModel(Vacancy $vacancy): self
    {
        return new self(
            id: $vacancy->id,
            branch_id: $vacancy->branch_id,
            branch: $vacancy->branch?->only(['id', 'name', 'code']),
            department_id: $vacancy->department_id,
            department: $vacancy->department?->only(['id', 'name']),
            position_id: $vacancy->position_id,
            position: $vacancy->position?->only(['id', 'name']),
            location: $vacancy->location,
            openings: $vacancy->openings,
            supervisor: $vacancy->supervisor,
            education: $vacancy->education?->value,
            education_label: $vacancy->education?->label(),
            experience: $vacancy->experience?->value,
            experience_label: $vacancy->experience?->label(),
            languages: $vacancy->languages->map(fn (VacancyLanguage $language) => $language->name)->all(),
            skills: $vacancy->skills,
            requirements: $vacancy->requirements,
            responsibilities: $vacancy->responsibilities,
            employment_type: $vacancy->employment_type?->value,
            employment_type_label: $vacancy->employment_type?->label(),
            schedule_type: $vacancy->schedule_type?->value,
            schedule_type_label: $vacancy->schedule_type?->label(),
            schedule_other: $vacancy->schedule_other,
            work_format: $vacancy->work_format?->value,
            work_format_label: $vacancy->work_format?->label(),
            salary: $vacancy->salary,
            probation: $vacancy->probation?->value,
            probation_label: $vacancy->probation?->label(),
            probation_other: $vacancy->probation_other,
            opening_reason: $vacancy->opening_reason?->value,
            opening_reason_label: $vacancy->opening_reason?->label(),
            priority: $vacancy->priority?->value,
            priority_label: $vacancy->priority?->label(),
            status: $vacancy->status->value,
            opened_at: $vacancy->opened_at?->format('Y-m-d'),
            deadline: $vacancy->deadline?->format('Y-m-d'),
            closed_at: $vacancy->closed_at?->format('Y-m-d'),
            creator: $vacancy->creator?->only(['id', 'name']),
        );
    }
}
