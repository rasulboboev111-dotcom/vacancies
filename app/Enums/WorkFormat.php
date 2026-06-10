<?php

namespace App\Enums;

enum WorkFormat: string implements HasLabel
{
    use HasOptions;

    case OFFICE = 'офис';
    case REMOTE = 'удалённо';
    case HYBRID = 'гибрид';

    public function label(): string
    {
        return match ($this) {
            self::OFFICE => 'Офис',
            self::REMOTE => 'Удалённо',
            self::HYBRID => 'Гибрид',
        };
    }
}
