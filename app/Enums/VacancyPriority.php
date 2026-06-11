<?php

namespace App\Enums;

enum VacancyPriority: string implements HasLabel
{
    case LOW = 'низкая';
    case MEDIUM = 'средняя';
    case HIGH = 'высокая';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Низкая',
            self::MEDIUM => 'Средняя',
            self::HIGH => 'Высокая',
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
