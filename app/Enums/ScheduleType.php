<?php

namespace App\Enums;

enum ScheduleType: string implements HasLabel
{
    use HasOptions;

    case STANDARD = '5/2';
    case OTHER = 'иной';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => '5/2, 08:00–17:00',
            self::OTHER => 'Иной (сменный, 2/2, дежурство)',
        };
    }
}
