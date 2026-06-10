<?php

namespace App\Http\Requests\Vacancy;

use App\Models\Vacancy;
use Illuminate\Support\Facades\Gate;

class StoreVacancyRequest extends VacancyRequest
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
        $rules = parent::rules();
        $rules['openings'] = ['required', 'integer', 'min:1', 'max:10000'];

        return $rules;
    }
}
