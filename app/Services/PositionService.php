<?php

namespace App\Services;

use App\Exceptions\PositionInUseException;
use App\Models\Employee;
use App\Models\Position;

class PositionService
{
    public function create(array $data): Position
    {
        // Логирование create/update/delete — через трейт LogsActivity на модели
        // Position (детально, по полям), как у остальных сущностей.
        return Position::create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $position->update($data);

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

        $position->delete();
    }
}
