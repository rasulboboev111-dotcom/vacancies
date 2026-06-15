<?php

namespace App\Enums;

enum OpeningReason: string implements HasLabel
{
    use HasOptions;

    case EXPANSION = 'расширение штата';
    case NEW_POSITION = 'новая позиция';
    case REPLACEMENT = 'замена уволенного сотрудника';
    case MATERNITY = 'декретная ставка / временное замещение';

    public function label(): string
    {
        return match ($this) {
            self::EXPANSION => 'Расширение штата',
            self::NEW_POSITION => 'Новая позиция',
            self::REPLACEMENT => 'Замена уволенного сотрудника',
            self::MATERNITY => 'Декретная ставка / временное замещение',
        };
    }
}
