<?php

namespace App\Enums;

enum WorkFormat: string implements HasLabel
{
    case OFFICE = 'офис';
    case REMOTE = 'удалённо';
    case HYBRID = 'гибрид';

    public function label(): string
    {
        return match ($this) {
            self::OFFICE => 'Офис',
            self::REMOTE => 'Удалённо',
            self::HYBRID => 'Гибрид',
        };
    }

    /**
     * The backing values, handy for validation and `in_array` checks.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
