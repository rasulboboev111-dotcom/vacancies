<?php

namespace App\Enums;

enum OpeningReason: string implements HasLabel
{
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
