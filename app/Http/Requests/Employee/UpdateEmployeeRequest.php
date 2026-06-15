<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\Gate;

class UpdateEmployeeRequest extends EmployeeRequest
{
    /**
     * Пользователи филиала могут обновлять сотрудников своего филиала (политика)
     * и не могут переводить их в другой филиал.
     */
    public function authorize(): bool
    {
        $employee = Employee::find($this->route('id'));

        if ($employee === null || ! Gate::allows('update', $employee)) {
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
