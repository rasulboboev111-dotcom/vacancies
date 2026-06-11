<?php

namespace App\Enums;

enum VacancyPriority: string implements HasLabel
{
    use HasOptions;

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
}
