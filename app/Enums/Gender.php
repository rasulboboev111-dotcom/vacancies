<?php

namespace App\Enums;

enum Gender: string implements HasLabel
{
    use HasOptions;

    case MALE = 'мужской';
    case FEMALE = 'женский';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Мард',
            self::FEMALE => 'Зан',
        };
    }
}
