<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\Gate;

class StoreEmployeeRequest extends EmployeeRequest
{
    /**
     * Branch users may only add employees to their own branch.
     */
    public function authorize(): bool
    {
        if (! Gate::allows('create', Employee::class)) {
            return false;
        }

        $user = $this->user();

        if (! $user->isAdmin()) {
            if ($user->branch_id === null) {
                return false;
            }
            if ($this->filled('branch_id') && (int) $this->input('branch_id') !== (int) $user->branch_id) {
                return false;
            }
        }

        return true;
    }
}
