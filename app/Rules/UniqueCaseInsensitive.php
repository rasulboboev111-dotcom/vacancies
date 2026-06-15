<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Регистронезависимая уникальность по столбцу (LOWER(TRIM(col))) — повторяет
 * частичные unique-индексы БД (positions_name_lower_unique, email и т.п.). Общая
 * замена скопированных closure-валидаторов в Store/Update реквестах.
 *
 * @template TModel of Model
 */
class UniqueCaseInsensitive implements ValidationRule
{
    /**
     * @param  class-string<TModel>  $model
     */
    public function __construct(
        private readonly string $model,
        private readonly string $column,
        private readonly string $message,
        private readonly int|string|null $ignoreId = null,
    ) {
        // $column подставляется в raw SQL — он задаётся разработчиком, но на всякий
        // случай ограничиваем безопасным идентификатором.
        if (! preg_match('/^[a-z_]+$/', $column)) {
            throw new InvalidArgumentException("Unsafe column name: {$column}");
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $normalized = mb_strtolower(trim((string) $value));

        $query = $this->model::query()
            ->whereRaw("LOWER(TRIM({$this->column})) = ?", [$normalized]);

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail($this->message);
        }
    }
}
