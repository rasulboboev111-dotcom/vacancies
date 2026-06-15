<?php

namespace App\Support;

use App\Models\Position;

/**
 * Резолвинг должности (Position) при импорте оргструктуры. Имя должности —
 * локальная идентичность (positions требуют регистронезависимое уникальное имя),
 * поэтому сначала матчим по имени и лишь дописываем jobTitleId источника на эту
 * строку: источник правомерно переиспользует один и тот же текст должности под
 * несколькими jobTitleId. Кэши живут на время одного импорта (один экземпляр на
 * запуск). Вынесено из ImportOrgStructure, чтобы команда не несла эту логику.
 */
class PositionResolver
{
    /** @var array<int,int> external_id должности (jobTitleId) => positions.id */
    private array $byExternal = [];

    /** @var array<string,int> имя должности (в нижнем регистре) => positions.id */
    private array $byName = [];

    /**
     * Сопоставляет должность с id Position (создавая её при необходимости).
     */
    public function resolve(?int $externalId, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '' && $externalId === null) {
            return null;
        }

        // Именованные должности — первичная идентичность. Резолвим по имени,
        // затем привязываем id источника, только если он свободен — у таблицы ДВА
        // unique-ключа (регистронезависимое имя И external_id), а
        // firstOrCreate/create защитили бы лишь тот, по которому делают запрос,
        // оставляя второй бросить 23505, который прерывает всю транзакцию импорта.
        if ($name !== '') {
            $key = mb_strtolower($name);
            if (isset($this->byName[$key])) {
                return $this->byName[$key];
            }

            $position = $this->resolveOrCreate($name, $externalId);

            // Дописываем id источника на строку, где его нет — но никогда, если
            // этим id уже владеет другая должность.
            if ($externalId !== null
                && $position->external_id === null
                && ! $this->externalIdTaken($externalId, $position->id)
            ) {
                $position->update(['external_id' => $externalId]);
            }

            if ($position->external_id !== null) {
                $this->byExternal[$position->external_id] = $position->id;
            }

            return $this->byName[$key] = $position->id;
        }

        // Без имени: идентифицируем чисто по id источника, со сгенерированной меткой.
        if (isset($this->byExternal[$externalId])) {
            return $this->byExternal[$externalId];
        }

        $position = Position::where('external_id', $externalId)->first()
            ?? $this->resolveOrCreate('Вазифа '.$externalId, $externalId);

        $this->byName[mb_strtolower(trim($position->name))] = $position->id;

        return $this->byExternal[$externalId] = $position->id;
    }

    /**
     * Находит должность по её регистронезависимому имени либо создаёт. Сверяет оба
     * unique-ключа: существующее имя переиспользуется как есть, а id источника
     * привязывается к новой строке, только если он ещё не занят — так что ни
     * unique-индекс имени, ни external_id не смогут бросить 23505.
     */
    private function resolveOrCreate(string $name, ?int $externalId): Position
    {
        $existing = Position::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])->first();
        if ($existing !== null) {
            return $existing;
        }

        return Position::create([
            'name' => $name,
            'external_id' => $externalId !== null && ! $this->externalIdTaken($externalId) ? $externalId : null,
        ]);
    }

    /**
     * Владеет ли уже этим id источника другая должность (external_id уникален).
     */
    private function externalIdTaken(int $externalId, ?int $exceptId = null): bool
    {
        return Position::where('external_id', $externalId)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }
}
