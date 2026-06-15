<?php

namespace App\Enums;

enum Probation: string implements HasLabel
{
    use HasOptions;

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
}
