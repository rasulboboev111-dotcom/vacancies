<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('branch'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required', 'string', 'max:10',
                Rule::unique('branches', 'code')->whereNull('deleted_at')->ignore($this->route('branch')?->id),
            ],
            'address' => 'nullable|string|max:255',
        ];
    }
}
