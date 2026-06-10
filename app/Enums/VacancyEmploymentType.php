<?php

namespace App\Enums;

enum VacancyEmploymentType: string implements HasLabel
{
    use HasOptions;

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
}
