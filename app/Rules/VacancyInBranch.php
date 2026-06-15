<?php

namespace App\Rules;

use App\Models\Vacancy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Проверяет, что вакансия принадлежит заданному филиалу — чтобы заявку нельзя
 * было привязать к вакансии чужого филиала. Если филиал не задан (null),
 * проверяется только существование вакансии. Общая для Store/Update реквестов
 * (одно правило вместо копии в каждом).
 */
class VacancyInBranch implements ValidationRule
{
    public function __construct(private readonly ?int $branchId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query = Vacancy::whereKey($value);

        if ($this->branchId !== null) {
            $query->where('branch_id', $this->branchId);
        }

        if (! $query->exists()) {
            $fail('Вакансияи интихобшуда ба ин филиал тааллуқ надорад.');
        }
    }
}
