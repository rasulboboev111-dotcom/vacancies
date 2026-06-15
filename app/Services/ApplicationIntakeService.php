<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Branch;
use App\Models\Vacancy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ApplicationIntakeService
{
    /**
     * Upsert an application by external_id (idempotent two-phase intake) and
     * optionally store the résumé. By default every intake is attached to the
     * configured default branch (ҶСК «Тоҷиктелеком»); the vacancy is matched
     * within that branch. If the default branch is missing, branch/vacancy fall
     * back to resolution from the matching vacancy.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data, ?UploadedFile $resume): Application
    {
        return DB::transaction(function () use ($data, $resume) {
            $defaultBranchId = $this->defaultBranchId();

            if ($defaultBranchId !== null) {
                $branchId = $defaultBranchId;
                $vacancyId = $this->resolveVacancyId($data['vacancy'] ?? null, $defaultBranchId);
            } else {
                [$branchId, $vacancyId] = $this->resolveVacancy($data['vacancy'] ?? null);
            }

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
     * Id филиала по умолчанию (ҶСК «Тоҷиктелеком») по businessUnit code, либо
     * null, если такой филиал не заведён.
     */
    private function defaultBranchId(): ?int
    {
        $code = (string) config('intake.default_branch_code');
        if ($code === '') {
            return null;
        }

        $id = Branch::where('code', $code)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Находит вакансию по названию должности в пределах указанного филиала.
     */
    private function resolveVacancyId(?string $title, int $branchId): ?int
    {
        $title = trim((string) $title);
        if ($title === '') {
            return null;
        }

        $id = Vacancy::where('branch_id', $branchId)
            ->whereHas('position', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$title]))
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Запасное определение филиала и вакансии из совпавшей вакансии — когда
     * филиал по умолчанию не настроен/не найден.
     *
     * @return array{0: int|null, 1: int|null} [branch_id, vacancy_id]
     */
    private function resolveVacancy(?string $title): array
    {
        $title = trim((string) $title);
        if ($title === '') {
            return [null, null];
        }

        $vacancy = Vacancy::whereHas('position', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$title]))->first();

        return $vacancy ? [$vacancy->branch_id, $vacancy->id] : [null, null];
    }
}
