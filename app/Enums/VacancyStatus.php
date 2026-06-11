<?php

namespace App\Enums;

enum VacancyStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Кушода',
            self::CLOSED => 'Пӯшида',
        };
    }

    /**
     * Backing-значения, удобные для валидации и проверок `in_array`.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
