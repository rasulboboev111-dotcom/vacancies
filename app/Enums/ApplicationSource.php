<?php

namespace App\Enums;

/**
 * Источник отклика: канал, по которому пришла заявка. Единый источник правды
 * вместо строковых литералов, разбросанных по контроллеру/реквестам/фронту.
 */
enum ApplicationSource: string implements HasLabel
{
    use HasOptions;

    case TELEGRAM = 'telegram';
    case EMAIL = 'email';
    case SOMON = 'somon';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::TELEGRAM => 'Telegram',
            self::EMAIL => 'Email',
            self::SOMON => 'Somon.tj',
            self::MANUAL => 'Дастӣ',
        };
    }
}
