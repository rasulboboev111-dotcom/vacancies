<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Crash-safe date validation. Laravel's native date comparison rules
 * (after_or_equal/before_or_equal/...) convert the value to a Unix timestamp,
 * which throws an unhandled "Epoch doesn't fit in a PHP integer" ValueError
 * (HTTP 500) for absurdly large years. This rule validates the format, caps the
 * year, and performs the optional "after or equal" comparison using Carbon's
 * datetime comparison — never getTimestamp() — so an out-of-range year fails
 * cleanly with a 422 instead of crashing.
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
     * Parse a date string without ever touching the epoch. Returns null for
     * empty, unparseable, or out-of-range-low values (year < 1000) so callers
     * treat them as invalid rather than crashing.
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
