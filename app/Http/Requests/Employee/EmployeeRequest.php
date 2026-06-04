<?php

namespace App\Http\Requests\Employee;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Rules\SaneDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Shared validation rules for creating and updating an employee. The owning
 * row (on update) is ignored by the INN/passport unique checks; empty values
 * are exempt so they may repeat.
 */
abstract class EmployeeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = (int) $this->input('branch_id');
        $employee = $this->route('employee');
        $ignoreId = $employee?->id;
        $currentManagerId = $employee?->manager_id;
        $currentBranchId = $employee?->branch_id;

        // Validate manager_id strictly (same branch, not soft-deleted, not self)
        // EXCEPT when nothing relevant changed: same existing manager AND same
        // branch. That spares an unrelated edit from re-validating a manager that
        // is legitimately archived or cross-branch (e.g. set by org:import). A
        // branch move, or a null->value change, still goes through the strict rule.
        $managerId = $this->input('manager_id');
        $managerUnchanged = $ignoreId !== null
            && $managerId !== null
            && $currentManagerId !== null
            && (int) $managerId === (int) $currentManagerId
            && $branchId === (int) $currentBranchId;

        $managerRules = ['nullable', 'integer'];
        if (! $managerUnchanged) {
            $managerRules[] = Rule::exists('employees', 'id')->where(function ($q) use ($branchId, $ignoreId) {
                $q->where('branch_id', $branchId)->whereNull('deleted_at');
                if ($ignoreId) {
                    $q->where('id', '!=', $ignoreId);
                }
            });
        }

        return [
            'branch_id' => 'required|exists:branches,id',
            'category' => ['required', Rule::enum(Category::class)],
            'type_id' => ['required', Rule::enum(EmploymentType::class)],
            'full_name' => 'required|string|max:255',
            'gender' => ['required', Rule::enum(Gender::class)],
            'position_id' => 'required|exists:positions,id',
            'department_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'manager_id' => $managerRules,
            'hire_date' => ['required', new SaneDate],
            'dismissal_date' => ['nullable', new SaneDate(afterOrEqualField: 'hire_date')],
            'dismissal_reason' => 'nullable|string|max:500|required_with:dismissal_date',
            'birth_date' => ['nullable', new SaneDate],
            'nationality' => 'nullable|string|max:255',
            'passport_number' => [
                'nullable', 'string', 'max:255',
                $this->uniqueLiveValue('passport_number', $ignoreId),
            ],
            'passport_start_date' => ['nullable', new SaneDate],
            'passport_end_date' => ['nullable', new SaneDate],
            'passport_issued_by' => 'nullable|string|max:255',
            'inn' => [
                'nullable', 'string', 'max:50',
                $this->uniqueLiveValue('inn', $ignoreId),
            ],
            'sin' => [
                'nullable', 'string', 'max:255',
                $this->uniqueLiveValue('sin', $ignoreId),
            ],
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'email' => [
                'nullable', 'email', 'max:255',
                $this->uniqueLiveValue('email', $ignoreId),
            ],
            'birth_place' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'employment_start_date' => ['nullable', new SaneDate],
        ];
    }

    /**
     * Soft-delete-aware uniqueness for a government-issued identifier: the value
     * must be unique among live (non-trashed) employees, empty values may repeat,
     * and the row being updated is ignored.
     */
    private function uniqueLiveValue(string $column, mixed $ignoreId): Unique
    {
        return Rule::unique('employees', $column)
            ->where(fn ($q) => $q->whereNull('deleted_at')->where($column, '!=', ''))
            ->ignore($ignoreId);
    }
}
