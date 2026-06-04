<?php

namespace App\Http\Requests\Vacancy;

use App\Enums\EmploymentType;
use App\Models\Vacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreVacancyRequest extends FormRequest
{
    /**
     * The branch_id the request originally asked for, captured before it is
     * normalized in prepareForValidation() so authorize() can detect a branch
     * user trying to target another branch.
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
     * Pin the vacancy to a branch: admins choose it, branch users to their own.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $this->requestedBranchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;

        $branchId = $user->isAdmin()
            ? $this->integer('branch_id')
            : (int) ($user->branch_id ?? 0);

        // A vacancy is for at least one person; default to 1 when unspecified.
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
            'title' => ['required', 'string', 'max:255'],
            'openings' => ['required', 'integer', 'min:1', 'max:10000'],
            'employment_type' => ['nullable', Rule::in($employmentTypes)],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'opened_at' => ['nullable', 'date'],
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
