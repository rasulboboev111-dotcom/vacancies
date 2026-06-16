<?php

namespace App\Http\Requests\Application;

use App\Enums\ApplicationSource;
use App\Http\Requests\Concerns\ResumeRules;
use App\Models\Application;
use App\Rules\VacancyInBranch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    use ResumeRules;

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
            'source' => $this->filled('source') ? $this->input('source') : ApplicationSource::MANUAL->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $branchId = $this->input('branch_id');

        return [
            'branch_id' => $user->isAdmin()
                ? ['nullable', 'integer', 'exists:branches,id']
                : ['required', 'integer', 'exists:branches,id'],
            'vacancy_id' => ['nullable', 'integer', new VacancyInBranch($branchId ? (int) $branchId : null)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'source' => ['nullable', Rule::in(ApplicationSource::values())],
            'resume' => $this->resumeRules(),
        ];
    }
}
