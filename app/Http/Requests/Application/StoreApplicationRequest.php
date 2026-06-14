<?php

namespace App\Http\Requests\Application;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! Gate::allows('create', Application::class)) {
            return false;
        }

        $user = $this->user();

        if (! $user->isAdmin() && $user->branch_id === null) {
            return false;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $branchId = $user->isAdmin()
            ? ($this->filled('branch_id') ? (int) $this->input('branch_id') : null)
            : (int) ($user->branch_id);

        $this->merge([
            'branch_id' => $branchId,
            'source' => $this->filled('source') ? $this->input('source') : 'manual',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'branch_id' => $user->isAdmin()
                ? ['nullable', 'integer', 'exists:branches,id']
                : ['required', 'integer', 'exists:branches,id'],
            'vacancy_id' => ['nullable', 'integer', 'exists:vacancies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'source' => ['nullable', 'string', 'max:32'],
            'resume' => [
                'nullable',
                'file',
                'mimes:'.implode(',', config('intake.resume_mimes')),
                'max:'.config('intake.resume_max_kb'),
            ],
        ];
    }
}
