<?php

namespace App\Http\Requests\Employee;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Employee;
use App\Rules\SaneDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Общие правила валидации для создания и обновления сотрудника. Редактируемая
 * запись (при обновлении) исключается из проверок уникальности ИНН/паспорта;
 * пустые значения освобождены от проверки и могут повторяться.
 */
abstract class EmployeeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = (int) $this->input('branch_id');
        $employeeId = $this->route('id');
        $employee = $employeeId !== null ? Employee::find($employeeId) : null;
        $ignoreId = $employee?->id;
        $currentManagerId = $employee?->manager_id;
        $currentBranchId = $employee?->branch_id;

        // Строгая валидация manager_id (тот же филиал, не удалён мягко, не сам
        // сотрудник), КРОМЕ случая, когда ничего значимого не изменилось: тот же
        // существующий руководитель И тот же филиал. Это избавляет несвязанное
        // редактирование от повторной проверки руководителя, который законно
        // архивирован или из другого филиала (например, заданного org:import).
        // Смена филиала или переход null->значение всё равно проходят строгое правило.
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
     * Проверка уникальности с учётом мягкого удаления для государственного
     * идентификатора: значение должно быть уникальным среди живых (не удалённых)
     * сотрудников, пустые значения могут повторяться, а редактируемая запись
     * исключается.
     */
    private function uniqueLiveValue(string $column, mixed $ignoreId): Unique
    {
        return Rule::unique('employees', $column)
            ->where(fn ($q) => $q->whereNull('deleted_at')->where($column, '!=', ''))
            ->ignore($ignoreId);
    }
}
