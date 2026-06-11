<?php

namespace App\Enums;

enum VacancyStatus: string implements HasLabel
{
    use HasOptions;

    case OPEN = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Кушода',
            self::CLOSED => 'Пӯшида',
        };
    }
}
