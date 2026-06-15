<?php

namespace App\Data;

use App\Models\Application;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelData\Data;

/**
 * The shape of an application row sent to the Applications/Index page. Keys mirror the
 * column/relation names the Vue components already read.
 */
class ApplicationData extends Data
{
    /**
     * @param  array<string, mixed>|null  $branch
     * @param  array<string, mixed>|null  $vacancy
     * @param  array<string, mixed>|null  $survey
     */
    public function __construct(
        public int $id,
        public ?int $external_id,
        public ?int $branch_id,
        public ?array $branch,
        public ?int $vacancy_id,
        public ?array $vacancy,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $vacancy_title,
        public ?string $source,
        public ?string $summary,
        public ?array $survey,
        public bool $has_resume,
        public ?string $resume_filename,
        public ?string $resume_download_url,
        public ?string $created_at,
    ) {}

    public static function fromModel(Application $a): self
    {
        $resume = $a->getFirstMedia('resumes');

        return new self(
            id: $a->id,
            external_id: $a->external_id,
            branch_id: $a->branch_id,
            branch: $a->branch?->only(['id', 'name', 'code']),
            vacancy_id: $a->vacancy_id,
            vacancy: $a->vacancy ? ['id' => $a->vacancy->id, 'title' => $a->vacancy->displayName()] : null,
            name: $a->name,
            email: $a->email,
            phone: $a->phone,
            vacancy_title: $a->vacancy_title,
            source: $a->source,
            summary: $a->summary,
            survey: $a->survey,
            has_resume: $resume !== null,
            resume_filename: $resume?->file_name,
            resume_download_url: $resume && Route::has('applications.resume')
                ? route('applications.resume', $a->id)
                : null,
            created_at: $a->created_at?->format('Y-m-d H:i'),
        );
    }
}
