<?php

namespace App\Http\Requests\Department;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * The branch_id the request originally asked for, captured before it is
     * normalized so authorize() can reject a branch user targeting another.
     */
    private ?int $requestedBranchId = null;

    public function authorize(): bool
    {
        if (! Gate::allows('create', Department::class)) {
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

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $this->requestedBranchId = $this->filled('branch_id') ? (int) $this->input('branch_id') : null;

        $branchId = $user->isAdmin()
            ? $this->integer('branch_id')
            : (int) ($user->branch_id ?? 0);

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
                        )),
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
