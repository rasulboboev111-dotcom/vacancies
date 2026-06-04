<?php

namespace App\Services;

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
        $data['created_by'] = $creator->id;
        $data['status'] = VacancyStatus::OPEN;
        $data['opened_at'] = $data['opened_at'] ?? Carbon::today()->toDateString();
        $data['closed_at'] = null;

        // The row save + audit entry are atomic.
        return DB::transaction(function () use ($data) {
            $vacancy = new Vacancy($data);
            $vacancy->disableLogging()->save();

            activity()
                ->performedOn($vacancy)
                ->event('created')
                ->log("Вакансия эҷод шуд: {$vacancy->title}");

            return $vacancy;
        });
    }

    public function update(Vacancy $vacancy, array $data, ?string $status): Vacancy
    {
        // See create(): position resolution stays outside the transaction.
        $data = $this->resolvePosition($data);

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

        return DB::transaction(function () use ($vacancy, $data) {
            $vacancy->disableLogging()->update($data);

            activity()
                ->performedOn($vacancy)
                ->event('updated')
                ->log("Вакансия навсозӣ шуд: {$vacancy->title}");

            return $vacancy;
        });
    }

    public function delete(Vacancy $vacancy): void
    {
        $title = $vacancy->title;

        // Delete then log, both in one transaction — no phantom "deleted" log if
        // the delete fails, no lost log if the audit write fails after it.
        DB::transaction(function () use ($vacancy, $title) {
            $vacancy->disableLogging()->delete();

            activity()
                ->performedOn($vacancy)
                ->event('deleted')
                ->log("Вакансия нест карда шуд: {$title}");
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
}
