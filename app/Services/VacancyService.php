<?php

namespace App\Services;

use App\Enums\Probation;
use App\Enums\ScheduleType;
use App\Enums\VacancyStatus;
use App\Models\Position;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VacancyService
{
    public function __construct(private readonly LookupResolver $lookups) {}

    public function create(array $data, User $creator): Vacancy
    {
        // Resolve the free-text position BEFORE the transaction: LookupResolver
        // recovers from a concurrent-insert unique violation by re-reading, which
        // a PostgreSQL aborted-transaction would break. An unused Position row on
        // rollback is harmless reference data.
        $data = $this->resolvePosition($data);
        $data = $this->clearDanglingOtherFields($data);
        $languages = $this->pullLanguages($data);
        $data['created_by'] = $creator->id;
        $data['status'] = VacancyStatus::OPEN;
        $data['opened_at'] = $data['opened_at'] ?? Carbon::today()->toDateString();
        $data['closed_at'] = null;

        // The row save + languages + audit entry are atomic.
        return DB::transaction(function () use ($data, $languages) {
            $vacancy = new Vacancy($data);
            $vacancy->disableLogging()->save();

            $this->syncLanguages($vacancy, $languages);

            activity()
                ->performedOn($vacancy)
                ->event('created')
                ->log("Вакансия эҷод шуд: {$vacancy->displayName()}");

            return $vacancy;
        });
    }

    public function update(Vacancy $vacancy, array $data, ?string $status): Vacancy
    {
        // See create(): position resolution stays outside the transaction.
        $data = $this->resolvePosition($data);
        $data = $this->clearDanglingOtherFields($data);
        $languages = $this->pullLanguages($data);

        // Pure in-memory status/closed_at derivation — no DB access, so keep it
        // out of the transaction (which only needs to wrap the update + audit log).
        $newStatus = $status !== null ? VacancyStatus::tryFrom($status) : null;
        if ($newStatus !== null) {
            $data['status'] = $newStatus;

            if ($newStatus === VacancyStatus::CLOSED && $vacancy->status !== VacancyStatus::CLOSED) {
                $data['closed_at'] = Carbon::today()->toDateString();
            }

            if ($newStatus === VacancyStatus::OPEN) {
                $data['closed_at'] = null;
            }
        }

        return DB::transaction(function () use ($vacancy, $data, $languages) {
            $vacancy->disableLogging()->update($data);

            $this->syncLanguages($vacancy, $languages);

            activity()
                ->performedOn($vacancy)
                ->event('updated')
                ->log("Вакансия навсозӣ шуд: {$vacancy->displayName()}");

            return $vacancy;
        });
    }

    public function delete(Vacancy $vacancy): void
    {
        $name = $vacancy->displayName();

        // Delete then log, both in one transaction — no phantom "deleted" log if
        // the delete fails, no lost log if the audit write fails after it.
        DB::transaction(function () use ($vacancy, $name) {
            $vacancy->disableLogging()->delete();

            activity()
                ->performedOn($vacancy)
                ->event('deleted')
                ->log("Вакансия нест карда шуд: {$name}");
        });
    }

    /**
     * Translate the free-text "position" field into a position_id, creating the
     * position row on the fly (case-insensitive find-or-create). The key is
     * only touched when present, so a partial update (e.g. a status toggle)
     * never clears an existing position.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolvePosition(array $data): array
    {
        if (array_key_exists('position', $data)) {
            $data['position_id'] = $this->lookups->resolve(Position::class, $data['position']);
            unset($data['position']);
        }

        return $data;
    }

    /**
     * The «иной/иное» free-text columns only mean something while their option
     * is selected — when a request switches the group to a preset option, the
     * stale text is dropped so the row can't carry contradictory data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clearDanglingOtherFields(array $data): array
    {
        if (array_key_exists('schedule_type', $data) && $data['schedule_type'] !== ScheduleType::OTHER->value) {
            $data['schedule_other'] = null;
        }

        if (array_key_exists('probation', $data) && $data['probation'] !== Probation::OTHER->value) {
            $data['probation_other'] = null;
        }

        return $data;
    }

    /**
     * Extract the «Знание языков» multi-select for the child table. Null means
     * "the request did not touch languages" (partial update keeps them);
     * an array (possibly empty) replaces the existing set.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>|null
     */
    private function pullLanguages(array &$data): ?array
    {
        if (! array_key_exists('languages', $data)) {
            return null;
        }

        $languages = collect($data['languages'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values()
            ->all();

        unset($data['languages']);

        return $languages;
    }

    /**
     * @param  list<string>|null  $languages
     */
    private function syncLanguages(Vacancy $vacancy, ?array $languages): void
    {
        if ($languages === null) {
            return;
        }

        // Replace only when the set actually changed — the common edit that
        // doesn't touch languages costs one SELECT instead of a rewrite.
        $current = $vacancy->languages()->pluck('name')->sort()->values()->all();
        $target = collect($languages)->sort()->values()->all();
        if ($current === $target) {
            return;
        }

        $vacancy->languages()->delete();
        $vacancy->languages()->createMany(array_map(fn (string $name) => ['name' => $name], $languages));
    }
}
