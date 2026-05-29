<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'мужской';
    case FEMALE = 'женский';

    public function label(): string
    {
        return match($this) {
            self::MALE => 'Мужской',
            self::FEMALE => 'Женский',
        };
    }
}
