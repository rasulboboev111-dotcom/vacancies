<?php

namespace App\Http\Requests\Employee;

use Illuminate\Support\Facades\Gate;

class UpdateEmployeeRequest extends EmployeeRequest
{
    /**
     * Branch users may update employees of their own branch (policy) and may
     * not transfer them to another branch.
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
}
