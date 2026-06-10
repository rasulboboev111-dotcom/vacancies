<?php

namespace App\Enums;

enum Experience: string implements HasLabel
{
    use HasOptions;

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
}
