<?php

namespace App\Enums;

enum VacancyEmploymentType: string implements HasLabel
{
    case FULL = 'полная';
    case PARTIAL = 'частичная';
    case PROJECT = 'проектная';

    public function label(): string
    {
        return match ($this) {
            self::FULL => 'Полная',
            self::PARTIAL => 'Частичная',
            self::PROJECT => 'Проектная / срочная',
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
