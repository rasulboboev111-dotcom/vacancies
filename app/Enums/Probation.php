<?php

namespace App\Enums;

enum Probation: string implements HasLabel
{
    case NONE = 'нет';
    case ONE_MONTH = '1 месяц';
    case THREE_MONTHS = '3 месяца';
    case OTHER = 'иное';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Нет',
            self::ONE_MONTH => '1 мес.',
            self::THREE_MONTHS => '3 мес.',
            self::OTHER => 'Иное',
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
