<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ApplicationIntakeService
{
    /**
     * Upsert an application by external_id (idempotent two-phase intake) and
     * optionally store the résumé. Branch/vacancy resolved from the title.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data, ?UploadedFile $resume): Application
    {
        return DB::transaction(function () use ($data, $resume) {
            [$branchId, $vacancyId] = $this->resolveVacancy($data['vacancy'] ?? null);

            $attrs = [
                'name' => $data['name'] ?? 'Номаълум',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'vacancy_title' => $data['vacancy'] ?? null,
                'source' => $data['source'] ?? null,
                'summary' => $data['summary'] ?? null,
                'survey' => $data['survey'] ?? null,
                'source_created_at' => $data['created_at'] ?? null,
            ];
            // Only set branch/vacancy when resolved — never clobber an existing value with null.
            if ($branchId !== null) {
                $attrs['branch_id'] = $branchId;
            }
            if ($vacancyId !== null) {
                $attrs['vacancy_id'] = $vacancyId;
            }

            $application = Application::updateOrCreate(
                ['external_id' => $data['external_id']],
                $attrs
            );

            if ($resume instanceof UploadedFile) {
                $application->addMedia($resume)->toMediaCollection('resumes');
            }

            return $application;
        });
    }

    /**
     * @return array{0: int|null, 1: int|null} [branch_id, vacancy_id]
     */
    private function resolveVacancy(?string $title): array
    {
        $title = trim((string) $title);
        if ($title === '') {
            return [null, null];
        }

        $vacancy = Vacancy::where('title', $title)->first()
            ?? Vacancy::whereHas('position', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$title]))->first();

        return $vacancy ? [$vacancy->branch_id, $vacancy->id] : [null, null];
    }
}
