<?php

namespace App\Services;

use App\Models\BirthPlace;
use App\Models\Branch;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\Rotation;
use App\Models\Specialty;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(private readonly LookupResolver $lookups) {}

    public function create(array $data): Employee
    {
        // Resolve free-text lookups BEFORE the transaction: LookupResolver catches
        // a concurrent-insert unique violation and re-reads, but on PostgreSQL a
        // caught error inside a transaction poisons it (aborted state) and the
        // re-read would fail. An unused lookup row on rollback is harmless.
        $data = $this->normalize($data);

        // The row save + audit entry are atomic (no half-written record, no orphan log).
        return DB::transaction(function () use ($data) {
            $employee = new Employee($data);
            $employee->disableLogging()->save();
            $employee->load('position');

            activity()
                ->performedOn($employee)
                ->event('created')
                ->log("Корманд илова шуд: {$employee->full_name} ба вазифаи ".($employee->position?->name ?? 'Вазифаи номаълум'));

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        // See create(): lookup resolution stays outside the transaction.
        $data = $this->normalize($data);

        return DB::transaction(function () use ($employee, $data) {
            $employee->disableLogging()->update($data);

            activity()
                ->performedOn($employee)
                ->event('updated')
                ->log("Маълумоти корманд навсозӣ шуд: {$employee->full_name}");

            return $employee;
        });
    }

    /**
     * Reinstate a dismissed (archived) employee by clearing the dismissal date,
     * returning them to the active roster.
     */
    public function reinstate(Employee $employee): Employee
    {
        // Disable the auto-log to keep a single, explicit "reinstated" entry
        // instead of a generic "updated" diff.
        $employee->disableLogging()->update(['dismissal_date' => null, 'dismissal_reason' => null]);

        activity()
            ->performedOn($employee)
            ->event('updated')
            ->log("Корманд аз бойгонӣ барқарор карда шуд: {$employee->full_name}");

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $fullName = $employee->full_name;

        // One transaction so the delete and its audit entry commit together —
        // no phantom "deleted" log if the delete fails, no lost log if the
        // audit write fails after the delete.
        DB::transaction(function () use ($employee, $fullName) {
            $employee->disableLogging()->delete();

            activity()
                ->performedOn($employee)
                ->event('deleted')
                ->log("Корманд нест карда шуд: {$fullName}");
        });
    }

    public function rotate(Employee $employee, array $data): Rotation
    {
        // Names of the source branch/position before the move (for the log).
        $oldBranchName = $employee->branch?->name ?? 'Филиали номаълум';
        $oldPosition = $employee->position?->name ?? 'Вазифаи номаълум';

        // The rotation record and the employee move must be atomic — otherwise a
        // failed update would leave an orphaned rotation row.
        $rotation = DB::transaction(function () use ($employee, $data) {
            $rotation = Rotation::create([
                'employee_id' => $employee->id,
                'old_branch_id' => $employee->branch_id,
                'new_branch_id' => $data['branch_id'],
                'old_position_id' => $employee->position_id,
                'new_position_id' => $data['position_id'],
                'old_department_id' => $employee->department_id,
                'new_department_id' => $data['department_id'] ?? null,
                'rotation_date' => $data['rotation_date'],
                'reason' => $data['reason'] ?? null,
            ]);

            // Disable the auto-log; the rotation narrative below is the single entry.
            $employee->disableLogging()->update([
                'branch_id' => $data['branch_id'],
                'position_id' => $data['position_id'],
                'department_id' => $data['department_id'] ?? null,
            ]);

            return $rotation;
        });

        $newBranchName = Branch::find($data['branch_id'])?->name ?? 'Филиали номаълум';
        $newPositionName = Position::find($data['position_id'])?->name ?? 'Вазифаи номаълум';

        activity()
            ->performedOn($employee)
            ->event('updated')
            ->log("Ротатсияи корманд {$employee->full_name} анҷом дода шуд. Аз {$oldBranchName} ({$oldPosition}) ба {$newBranchName} ({$newPositionName}) гузаронида шуд");

        return $rotation;
    }

    /**
     * Translate the validated form payload into a column-ready attribute set:
     * map the employment type, and resolve the free-text vocabulary fields to
     * their normalized lookup FK ids (creating rows on the fly).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('type_id', $data)) {
            $data['employment_type'] = $data['type_id'];
            unset($data['type_id']);
        }

        $data['nationality_id'] = $this->lookups->resolve(Nationality::class, $data['nationality'] ?? null);
        $data['education_id'] = $this->lookups->resolve(Education::class, $data['education'] ?? null);
        $data['specialty_id'] = $this->lookups->resolve(Specialty::class, $data['specialty'] ?? null);
        $data['birth_place_id'] = $this->lookups->resolve(BirthPlace::class, $data['birth_place'] ?? null);

        unset($data['nationality'], $data['education'], $data['specialty'], $data['birth_place']);

        return $data;
    }
}
