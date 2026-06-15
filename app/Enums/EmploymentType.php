<?php

namespace App\Enums;

enum EmploymentType: string implements HasLabel
{
    use HasOptions;

    case FULL_TIME = 'штатный';
    case CONTRACT = 'контракт';

    public function label(): string
    {
        return match ($this) {
            self::FULL_TIME => 'Штатӣ',
            self::CONTRACT => 'Шартномавӣ',
        };
    }
}
