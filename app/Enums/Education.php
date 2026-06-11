<?php

namespace App\Enums;

enum Education: string implements HasLabel
{
    case HIGHER = 'высшее';
    case SECONDARY_SPECIAL = 'среднее специальное';
    case ANY = 'не имеет значения';

    public function label(): string
    {
        return match ($this) {
            self::HIGHER => 'Высшее',
            self::SECONDARY_SPECIAL => 'Среднее специальное',
            self::ANY => 'Не имеет значения',
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
