<?php

namespace App\Enums;

enum Education: string implements HasLabel
{
    use HasOptions;

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
}
