<?php

namespace App\Services;

use App\Enums\ApplicationSource;
use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\Branch;
use App\Models\Vacancy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ApplicationIntakeService
{
    /**
     * Upsert an application by external_id (idempotent two-phase intake) and
     * optionally store the résumé. Every intake is attached to the configured
     * default branch (ҶСК «Тоҷиктелеком»); the vacancy is matched within that
     * branch. If the default branch isn't configured/present, branch & vacancy
     * stay unresolved (null) for manual triage.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data, ?UploadedFile $resume): Application
    {
        return DB::transaction(function () use ($data, $resume) {
            $branchId = $this->defaultBranchId();
            $vacancyId = $branchId !== null
                ? $this->resolveVacancyId($data['vacancy'] ?? null, $branchId)
                : null;

            $attrs = [
                'name' => $data['name'] ?? 'Номаълум',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'vacancy_title' => $data['vacancy'] ?? null,
                // Источник всегда задан: отклики из бота приходят из Telegram, а
                // отсутствие канала нарушило бы инвариант «source не NULL»,
                // который держат ручные пути Store/Update.
                'source' => $data['source'] ?? ApplicationSource::TELEGRAM->value,
                'summary' => $data['summary'] ?? null,
                'survey' => $data['survey'] ?? null,
                'source_created_at' => $data['created_at'] ?? null,
            ];
            // Филиал не определён → не затираем существующие branch/vacancy
            // (временная недоступность дефолтного филиала не должна стирать
            // ранее сматченные значения). Если же филиал известен — пишем
            // vacancy_id по текущему совпадению названия (в т.ч. null), чтобы он
            // оставался согласован с vacancy_title и не висел устаревшим.
            if ($branchId !== null) {
                $attrs['branch_id'] = $branchId;
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

        // Только открытые вакансии — кандидата нельзя привязывать к уже закрытой.
        $id = Vacancy::where('branch_id', $branchId)
            ->where('status', VacancyStatus::OPEN)
            ->whereHas('position', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$title]))
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
