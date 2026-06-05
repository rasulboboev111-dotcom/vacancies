<?php

namespace App\Http\Requests\Vacancy;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('vacancy'));
    }

    /**
     * Admins may move the vacancy to the chosen branch (falling back to its
     * current one); branch users keep it on its existing branch.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $vacancy = $this->route('vacancy');

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
        $branchId = (int) $this->input('branch_id');
        $employmentTypes = array_map(fn (EmploymentType $type) => $type->value, EmploymentType::cases());

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'position' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'openings' => ['sometimes', 'required', 'integer', 'min:1', 'max:10000'],
            'employment_type' => ['nullable', Rule::in($employmentTypes)],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'opened_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(VacancyStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'филиал',
            'department_id' => 'шуъба',
            'position' => 'вазифа',
            'title' => 'ном',
            'openings' => 'шумораи кормандон',
            'employment_type' => 'намуди шуғл',
            'requirements' => 'талабот',
            'schedule' => 'ҷадвал',
            'salary' => 'маош',
            'description' => 'тавсиф',
            'opened_at' => 'санаи кушодашавӣ',
        ];
    }
}
