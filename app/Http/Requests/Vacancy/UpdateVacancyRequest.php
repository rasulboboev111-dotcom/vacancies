<?php

namespace App\Http\Requests\Vacancy;

use App\Enums\VacancyStatus;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateVacancyRequest extends VacancyRequest
{
    public function authorize(): bool
    {
        $vacancy = Vacancy::find($this->route('id'));

        return $vacancy !== null && Gate::allows('update', $vacancy);
    }

    /**
     * Администраторы могут переместить вакансию в выбранный филиал (с откатом к
     * текущему); пользователи филиала оставляют её в существующем филиале.
     */
    protected function prepareForValidation(): void
    {
        $vacancy = Vacancy::find($this->route('id'));

        // prepareForValidation() выполняется до authorize(); отсутствующий/мягко
        // удалённый id отклоняется там, поэтому выходим до разыменования null-модели
        // (иначе неверный id вернёт 500 вместо 403).
        if ($vacancy === null) {
            return;
        }

        $branchId = $this->user()->isAdmin()
            ? ($this->integer('branch_id') ?: $vacancy->branch_id)
            : (int) $vacancy->branch_id;

        $this->merge(['branch_id' => $branchId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['status'] = ['nullable', Rule::enum(VacancyStatus::class)];

        return $rules;
    }

    protected function storedOpenedAt(): ?string
    {
        return Vacancy::find($this->route('id'))?->opened_at?->format('Y-m-d');
    }
}
