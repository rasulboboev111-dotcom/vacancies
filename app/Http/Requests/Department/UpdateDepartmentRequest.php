<?php

namespace App\Http\Requests\Department;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $department = Department::find($this->route('id'));

        return $department !== null && Gate::allows('update', $department);
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $department = Department::find($this->route('id'));

        // prepareForValidation() выполняется до authorize(); отсутствующий/мягко
        // удалённый id отклоняется там (403), поэтому выходим до разыменования
        // null-модели — иначе неверный id вернул бы 500 вместо 403.
        if ($department === null) {
            return;
        }

        $branchId = $user->isAdmin()
            ? $this->integer('branch_id')
            : (int) $department->branch_id;

        $this->merge(['branch_id' => $branchId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $branchId = (int) $this->input('branch_id');
        $parentId = $this->input('parent_id');
        $parentId = $parentId === null || $parentId === '' ? null : (int) $parentId;

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query
                        ->where('branch_id', $branchId)
                        ->whereNull('deleted_at')
                        ->when(
                            $parentId,
                            fn ($query) => $query->where('parent_id', $parentId),
                            fn ($query) => $query->whereNull('parent_id'),
                        ))
                    ->ignore($this->route('id')),
            ],
            'code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => 'филиал',
            'parent_id' => 'шуъбаи болоӣ',
            'name' => 'ном',
            'code' => 'рамз',
        ];
    }
}
