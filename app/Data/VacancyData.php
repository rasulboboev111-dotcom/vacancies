<?php

namespace App\Data;

use App\Models\Vacancy;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The shape of a vacancy row sent to the Vacancies/Index page. Keys mirror the
 * column/relation names the Vue components already read.
 */
#[TypeScript]
class VacancyData extends Data
{
    /**
     * @param  array<string, mixed>|null  $branch
     * @param  array<string, mixed>|null  $department
     * @param  array<string, mixed>|null  $position
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
        public string $title,
        public int $openings,
        public ?string $employment_type,
        public ?string $requirements,
        public ?string $schedule,
        public ?string $salary,
        public ?string $description,
        public ?string $status,
        public ?string $opened_at,
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
            title: $vacancy->title,
            openings: $vacancy->openings,
            employment_type: $vacancy->employment_type?->value,
            requirements: $vacancy->requirements,
            schedule: $vacancy->schedule,
            salary: $vacancy->salary,
            description: $vacancy->description,
            status: $vacancy->status->value,
            opened_at: $vacancy->opened_at?->format('Y-m-d'),
            closed_at: $vacancy->closed_at?->format('Y-m-d'),
            creator: $vacancy->creator?->only(['id', 'name']),
        );
    }
}
