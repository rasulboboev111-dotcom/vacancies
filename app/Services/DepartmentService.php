<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function create(array $data): Department
    {
        $data['branch_id'] = (int) $data['branch_id'];
        $data['parent_id'] = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        $this->assertParentIsValid($data['parent_id'], $data['branch_id']);

        $department = new Department($data);
        $department->disableLogging()->save();

        activity()
            ->performedOn($department)
            ->event('created')
            ->log("Шуъба эҷод шуд: {$department->name}");

        return $department;
    }

    public function update(Department $department, array $data): Department
    {
        $data['branch_id'] = (int) $data['branch_id'];
        $data['parent_id'] = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        $this->assertParentIsValid($data['parent_id'], $data['branch_id'], $department);

        if ($department->wouldCreateCycle($data['parent_id'])) {
            throw ValidationException::withMessages([
                'parent_id' => 'Зершуъбаро ҳамчун шуъбаи болоӣ таъин кардан мумкин нест.',
            ]);
        }

        $department->disableLogging()->update($data);

        activity()
            ->performedOn($department)
            ->event('updated')
            ->log("Шуъба навсозӣ шуд: {$department->name}");

        return $department;
    }

    public function delete(Department $department): bool
    {
        $name = $department->name;

        // The child-existence guard runs INSIDE the transaction so a concurrent
        // sub-department insert cannot slip between the check and the delete.
        // Soft-deleting does not fire the DB-level ON DELETE SET NULL (that only
        // triggers on a hard delete), so detach references explicitly;
        // withTrashed() also clears archived employees/vacancies.
        $deleted = DB::transaction(function () use ($department) {
            // Include soft-deleted children: one still points here via parent_id,
            // so blocking on it prevents a dangling parent_id after the child is
            // later restored.
            if (Department::withTrashed()->where('parent_id', $department->id)->exists()) {
                return false;
            }

            Employee::withTrashed()
                ->where('department_id', $department->id)
                ->update(['department_id' => null]);

            Vacancy::withTrashed()
                ->where('department_id', $department->id)
                ->update(['department_id' => null]);

            $department->disableLogging()->delete();

            return true;
        });

        if (! $deleted) {
            return false;
        }

        activity()
            ->performedOn($department)
            ->event('deleted')
            ->log("Шуъба нест карда шуд: {$name}");

        return true;
    }

    /**
     * Ensure a chosen parent exists, is not the department itself, and lives in
     * the same branch. Throws a validation error otherwise.
     */
    private function assertParentIsValid(?int $parentId, int $branchId, ?Department $department = null): void
    {
        if ($parentId === null) {
            return;
        }

        if ($department && $parentId === (int) $department->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Шуъба наметавонад шуъбаи болоии худаш бошад.',
            ]);
        }

        $parent = Department::query()->find($parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Шуъбаи болоии интихобшуда ёфт нашуд.',
            ]);
        }

        if ((int) $parent->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Шуъбаи болоӣ бояд ба филиали интихобшуда тааллуқ дошта бошад.',
            ]);
        }
    }
}
