<?php

namespace App\Http\Requests\Vacancy;

use App\Models\Vacancy;
use Illuminate\Support\Facades\Gate;

class StoreVacancyRequest extends VacancyRequest
{
    /**
     * Изначально запрошенный branch_id, сохранённый до нормализации в
     * prepareForValidation(), чтобы authorize() мог распознать пользователя
     * филиала, пытающегося нацелиться на чужой филиал.
     */
    private ?int $requestedBranchId = null;

    public function authorize(): bool
    {
        if (! Gate::allows('create', Vacancy::class)) {
            return false;
        }

        $user = $this->user();

        if (! $user->isAdmin()) {
            if ($user->branch_id === null) {
                return false;
            }
            if ($this->requestedBranchId !== null && $this->requestedBranchId !== (int) $user->branch_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Привязывает вакансию к филиалу: администраторы выбирают его, пользователи
     * филиала — к своему.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $this->requestedBranchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;

        $branchId = $user->isAdmin()
            ? $this->integer('branch_id')
            : (int) ($user->branch_id ?? 0);

        // Вакансия открывается минимум на одного человека; по умолчанию 1, если не указано.
        $this->merge([
            'branch_id' => $branchId,
            'openings' => $this->filled('openings') ? $this->integer('openings') : 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['openings'] = ['required', 'integer', 'min:1', 'max:10000'];

        return $rules;
    }
}
