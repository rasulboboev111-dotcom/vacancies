<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class LookupResolver
{
    /**
     * Разрешает свободно вводимое значение в id строки справочника, создавая
     * строку, если её ещё нет. Пустой ввод даёт null. Сравнение
     * нечувствительно к регистру и пробелам, повторяя unique-индексы,
     * установленные на уровне базы данных.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function resolve(string $modelClass, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $model = $modelClass::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])->first();

        if (! $model) {
            try {
                $model = $modelClass::create(['name' => $name]);
            } catch (UniqueConstraintViolationException) {
                // Параллельный запрос вставил то же значение первым — перечитываем
                // его (нечувствительный к регистру unique-индекс гарантирует одну строку).
                $model = $modelClass::whereRaw('LOWER(TRIM(name)) = LOWER(?)', [$name])->firstOrFail();
            }
        }

        return $model->id;
    }
}
