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
        // Разрешаем свободно вводимую вазифу ДО транзакции: LookupResolver
        // восстанавливается после unique-нарушения параллельной вставки путём
        // перечитывания, что сломала бы прерванная транзакция PostgreSQL.
        // Неиспользованная строка Position при откате — безвредные справочные данные.
        $data = $this->findOrCreatePosition($data);
        $data = $this->clearDanglingOtherFields($data);
        [$languages, $data] = $this->pullLanguages($data);
        $data['created_by'] = $creator->id;
        $data['status'] = VacancyStatus::OPEN;
        $data['opened_at'] = $data['opened_at'] ?? Carbon::today()->toDateString();
        $data['closed_at'] = null;

        // Сохранение строки + языки + запись аудита атомарны. Логирование идёт
        // через трейт LogsActivity (детально, по полям) — как у заявок, чтобы в
        // журнале работала кнопка «Тафсилоти тағйирот».
        return DB::transaction(function () use ($data, $languages) {
            $vacancy = new Vacancy($data);
            $vacancy->save();

            $this->syncLanguages($vacancy, $languages);

            return $vacancy;
        });
    }

    public function update(Vacancy $vacancy, array $data, ?string $status): Vacancy
    {
        // См. create(): разрешение вазифы остаётся вне транзакции.
        $data = $this->findOrCreatePosition($data);
        $data = $this->clearDanglingOtherFields($data);
        [$languages, $data] = $this->pullLanguages($data);

        // Чисто in-memory вывод status/closed_at — без обращения к БД, поэтому
        // держим его вне транзакции (она должна обернуть только update + лог аудита).
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
            // Сериализуем параллельные правки одной вакансии, чтобы delete+insert
            // языков в syncLanguages() не привёл две транзакции к гонке за
            // unique-нарушение (vacancy_id, name).
            $locked = $vacancy->newQuery()->whereKey($vacancy->getKey())->lockForUpdate()->first();

            // Переносим атрибуты заблокированной строки в текущий экземпляр, чтобы
            // аудит-дифф (старое→новое) сравнивал с зафиксированным состоянием, а
            // не с устаревшим снимком в памяти.
            if ($locked !== null) {
                $vacancy->setRawAttributes($locked->getAttributes(), true);
            }

            // Авто-лог трейта пишет изменённые поля (старое→новое) — детально, как у заявок.
            $vacancy->update($data);

            $this->syncLanguages($vacancy, $languages);

            return $vacancy;
        });
    }

    public function delete(Vacancy $vacancy): void
    {
        // Удаление логируется трейтом LogsActivity (событие 'deleted').
        $vacancy->delete();
    }

    /**
     * Преобразует свободно вводимое поле «position» в position_id, создавая
     * строку вазифы на лету (нечувствительный к регистру find-or-create). Ключ
     * затрагивается только при его наличии, поэтому частичное обновление
     * (например, переключение статуса) никогда не очищает существующую вазифу.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function findOrCreatePosition(array $data): array
    {
        if (array_key_exists('position', $data)) {
            $data['position_id'] = $this->lookups->resolve(Position::class, $data['position']);
            unset($data['position']);
        }

        return $data;
    }

    /**
     * Свободно вводимые столбцы «иной/иное» имеют смысл, только пока выбран их
     * вариант — когда запрос переключает группу на предустановленный вариант,
     * устаревший текст сбрасывается, чтобы строка не несла противоречивых данных.
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
     * Извлекает мультивыбор «Знание языков» для дочерней таблицы. Null означает
     * «запрос не затрагивал языки» (частичное обновление сохраняет их);
     * массив (возможно пустой) заменяет существующий набор.
     *
     * Возвращает [languages, data без ключа languages] — без мутации аргумента
     * по ссылке (вызывающий явно переприсваивает $data).
     *
     * @param  array<string, mixed>  $data
     * @return array{0: list<string>|null, 1: array<string, mixed>}
     */
    private function pullLanguages(array $data): array
    {
        if (! array_key_exists('languages', $data)) {
            return [null, $data];
        }

        $languages = collect($data['languages'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values()
            ->all();

        unset($data['languages']);

        return [$languages, $data];
    }

    /**
     * @param  list<string>|null  $languages
     */
    private function syncLanguages(Vacancy $vacancy, ?array $languages): void
    {
        if ($languages === null) {
            return;
        }

        // Заменяем, только если набор действительно изменился — обычная правка,
        // не затрагивающая языки, стоит одного SELECT вместо перезаписи.
        $current = $vacancy->languages()->pluck('name')->sort()->values()->all();
        $target = collect($languages)->sort()->values()->all();
        if ($current === $target) {
            return;
        }

        $vacancy->languages()->delete();
        $vacancy->languages()->createMany(array_map(fn (string $name) => ['name' => $name], $languages));

        // VacancyLanguage не логируется автоматически (нет LogsActivity) и не входит
        // в logOnly вакансии, поэтому смену языков фиксируем отдельной аудит-записью.
        // На создании вакансии пропускаем — там уже есть запись 'created'.
        if (! $vacancy->wasRecentlyCreated) {
            activity()
                ->performedOn($vacancy)
                ->event('updated')
                ->log('Забонҳои вакансия тағйир дода шуданд: '.(implode(', ', $target) ?: '—'));
        }
    }
}
