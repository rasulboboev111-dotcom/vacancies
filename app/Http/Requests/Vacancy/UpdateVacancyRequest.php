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
     * Admins may move the vacancy to the chosen branch (falling back to its
     * current one); branch users keep it on its existing branch.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $vacancy = Vacancy::find($this->route('id'));

        $branchId = $user->isAdmin()
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
}
