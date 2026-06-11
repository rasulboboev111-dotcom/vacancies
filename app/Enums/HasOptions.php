<?php

namespace App\Enums;

/**
 * Общие помощники для enum-ов формы с метками: backing-значения для валидации
 * и пары {value, label} для групп выбора на фронтенде.
 */
trait HasOptions
{
    abstract public function label(): string;

    /**
     * Backing-значения, удобные для валидации и проверок `in_array`.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
