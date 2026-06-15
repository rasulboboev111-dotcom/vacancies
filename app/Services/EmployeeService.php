<?php

namespace App\Services;

use App\Models\BirthPlace;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Nationality;
use App\Models\Rotation;
use App\Models\Specialty;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(private readonly LookupResolver $lookups) {}

    public function create(array $data): Employee
    {
        // Разрешаем свободно вводимые справочники ДО транзакции: LookupResolver
        // ловит unique-нарушение от параллельной вставки и перечитывает строку,
        // но в PostgreSQL пойманная ошибка внутри транзакции переводит её в
        // прерванное (aborted) состояние, и перечитывание упадёт. Неиспользованная
        // строка справочника при откате безвредна.
        $data = $this->normalize($data);

        // Сохранение строки + запись аудита атомарны (нет недописанной записи, нет осиротевшего лога).
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
        // См. create(): разрешение справочников остаётся вне транзакции.
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
     * Восстанавливает уволенного (архивного) корманда, очищая дату увольнения и
     * возвращая его в активный состав.
     */
    public function reinstate(Employee $employee): Employee
    {
        // Обновление и его аудит-запись — в одной транзакции (как delete/rotate),
        // чтобы восстановление не осталось без явной записи «восстановлен».
        // Отключаем авто-лог, чтобы получить одну явную запись вместо diff.
        DB::transaction(function () use ($employee) {
            $employee->disableLogging()->update(['dismissal_date' => null, 'dismissal_reason' => null]);

            activity()
                ->performedOn($employee)
                ->event('updated')
                ->log("Корманд аз бойгонӣ барқарор карда шуд: {$employee->full_name}");
        });

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $fullName = $employee->full_name;

        // Одна транзакция, чтобы удаление и его запись аудита фиксировались
        // вместе — нет фантомного лога «удалён» при сбое удаления и нет
        // потерянного лога, если запись аудита упадёт после удаления.
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
        // Запись ротации и перевод корманда должны быть атомарны — иначе сбой
        // оставит осиротевшую ротацию или перевод. Детальный аудит (по полям
        // перехода) даёт сама Rotation::create через трейт LogsActivity, поэтому
        // дублирующий авто-лог на корманде отключаем.
        return DB::transaction(function () use ($employee, $data) {
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

            $employee->disableLogging()->update([
                'branch_id' => $data['branch_id'],
                'position_id' => $data['position_id'],
                'department_id' => $data['department_id'] ?? null,
            ]);

            return $rotation;
        });
    }

    /**
     * Преобразует проверенные данные формы в готовый к записи набор атрибутов:
     * маппит тип занятости и разрешает свободно вводимые словарные поля в
     * нормализованные FK-id справочников (создавая строки на лету).
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
