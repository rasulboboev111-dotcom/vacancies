<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $branch = Branch::find($this->route('id'));

        return $branch !== null && Gate::allows('update', $branch);
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
                Rule::unique('branches', 'code')->whereNull('deleted_at')->ignore($this->route('id')),
            ],
            'address' => 'nullable|string|max:255',
        ];
    }
}
