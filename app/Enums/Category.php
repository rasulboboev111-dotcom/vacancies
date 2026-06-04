<?php

namespace App\Enums;

/**
 * Employee category — a fixed value set, so it lives as a string-backed enum
 * (App\Enums) rather than a lookup table. The stored value is already the
 * display text, so label() returns it as-is.
 */
enum Category: string
{
    case SPECIALIST = 'Мутахассис';
    case WORKER = 'Коргар';
    case MANAGEMENT = 'Роҳбарият';

    public function label(): string
    {
        return $this->value;
    }
}
