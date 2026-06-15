<?php

namespace App\Enums;

/**
 * Категория сотрудника — фиксированный набор значений, поэтому реализована как
 * строковый enum (App\Enums), а не справочная таблица. Хранимое значение уже
 * является отображаемым текстом, поэтому label() возвращает его как есть.
 */
enum Category: string implements HasLabel
{
    use HasOptions;

    case SPECIALIST = 'Мутахассис';
    case WORKER = 'Коргар';
    case MANAGEMENT = 'Роҳбарият';

    public function label(): string
    {
        return $this->value;
    }
}
