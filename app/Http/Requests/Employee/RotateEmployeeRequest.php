<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RotateEmployeeRequest extends FormRequest
{
    /**
     * Branch users may rotate their own employees (policy) but not into
     * another branch.
     */
    public function authorize(): bool
    {
        if (! Gate::allows('update', $this->route('employee'))) {
            return false;
        }

        $user = $this->user();

        if (! $user->isAdmin()
            && $this->filled('branch_id')
            && (int) $this->input('branch_id') !== (int) $user->branch_id) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'position_id' => 'required|exists:positions,id',
            'department_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('branch_id', (int) $this->input('branch_id'))->whereNull('deleted_at'),
            ],
            'rotation_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ];
    }
}
