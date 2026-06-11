<?php

namespace App\Enums;

enum ScheduleType: string implements HasLabel
{
    case STANDARD = '5/2';
    case OTHER = 'иной';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => '5/2, 08:00–17:00',
            self::OTHER => 'Иной (сменный, 2/2, дежурство)',
        };
    }

    /**
     * The backing values, handy for validation and `in_array` checks.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
