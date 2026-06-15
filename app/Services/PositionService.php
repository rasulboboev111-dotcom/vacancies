<?php

namespace App\Services;

use App\Exceptions\PositionInUseException;
use App\Models\Employee;
use App\Models\Position;

class PositionService
{
    public function create(array $data): Position
    {
        $position = Position::create($data);

        activity()
            ->performedOn($position)
            ->event('created')
            ->log("Вазифаи нав эҷод шуд: {$position->name}");

        return $position;
    }

    public function update(Position $position, array $data): Position
    {
        $oldName = $position->name;
        $position->update($data);

        activity()
            ->performedOn($position)
            ->event('updated')
            ->log("Номи вазифа навсозӣ шуд: аз '{$oldName}' ба '{$position->name}'");

        return $position;
    }

    /**
     * @throws PositionInUseException когда с вазифой всё ещё связаны активные
     *                                или мягко удалённые корманды.
     */
    public function delete(Position $position): void
    {
        $employeeCount = Employee::withTrashed()->where('position_id', $position->id)->count();
        if ($employeeCount > 0) {
            throw new PositionInUseException("Вазифаи '{$position->name}'-ро нест кардан мумкин нест, зеро он ба кормандон таъин шудааст ({$employeeCount} нафар). Аввал онҳоро ба вазифаи дигар гузаронед.");
        }

        $name = $position->name;
        $position->delete();

        activity()
            ->performedOn($position)
            ->event('deleted')
            ->log("Вазифа нест карда шуд: {$name}");
    }
}
