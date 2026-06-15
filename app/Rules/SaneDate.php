<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Защищённая от падений валидация дат. Нативные правила сравнения дат Laravel
 * (after_or_equal/before_or_equal/...) переводят значение в Unix-таймстамп, что
 * для абсурдно больших годов бросает необработанный ValueError «Epoch doesn't fit
 * in a PHP integer» (HTTP 500). Это правило проверяет формат, ограничивает год и
 * выполняет необязательное сравнение «после или равно» через datetime-сравнение
 * Carbon — никогда getTimestamp() — так что год вне диапазона аккуратно
 * проваливается с 422 вместо падения.
 */
class SaneDate implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(
        private readonly int $maxYear = 3000,
        private readonly ?string $afterOrEqualField = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $date = $this->safeParse($value);

        if ($date === null) {
            $fail('Формати сана нодуруст аст.');

            return;
        }

        if ($date->year > $this->maxYear) {
            $fail("Сол набояд аз {$this->maxYear} зиёд бошад.");

            return;
        }

        if ($this->afterOrEqualField !== null) {
            $other = $this->safeParse($this->data[$this->afterOrEqualField] ?? null);

            if ($other !== null && $date->lessThan($other)) {
                $fail('Сана набояд пеш аз санаи қабул бошад.');
            }
        }
    }

    /**
     * Разбирает строку даты, ни разу не обращаясь к эпохе. Возвращает null для
     * пустых, неразбираемых или слишком малых значений (год < 1000), чтобы
     * вызывающий код считал их недопустимыми, а не падал.
     */
    private function safeParse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $date->year < 1000 ? null : $date;
    }
}
