<?php

namespace App\Enums;

enum Experience: string implements HasLabel
{
    case NONE = 'без опыта';
    case FROM_ONE_YEAR = 'от 1 года';
    case FROM_THREE_YEARS = 'от 3 лет и более';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Без опыта',
            self::FROM_ONE_YEAR => 'От 1 года',
            self::FROM_THREE_YEARS => 'От 3 лет и более',
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
