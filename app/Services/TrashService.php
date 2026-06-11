<?php

namespace App\Services;

use App\Exceptions\TrashConflictException;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

/**
 * Восстановление / окончательное удаление мягко удалённых записей. Каждое
 * восстановление повторно проверяет unique-индексы (живая запись могла занять
 * слот, пока строка лежала в корзине); каждое жёсткое удаление защищает внешние
 * ключи с RESTRICT. Заблокированная операция бросает TrashConflictException с
 * сообщением для пользователя.
 *
 * Авторизация остаётся в контроллере (политики); этот сервис владеет
 * транзакционным восстановлением/удалением и правилами конфликтов.
 */
class TrashService
{
    public function restoreEmployee(Employee $employee): void
    {
        $conflicts = collect([
            'external_id' => 'ID-и берунӣ',
            'inn' => 'РМА (ИНН)',
            'passport_number' => 'рақами шиноснома',
            'sin' => 'РСШ (SIN)',
            'email' => 'почтаи электронӣ',
        ])->filter(fn ($label, $col) => filled($employee->{$col})
            && Employee::where($col, $employee->{$col})->whereKeyNot($employee->id)->exists());

        if ($conflicts->isNotEmpty()) {
            throw new TrashConflictException("Барқарорсозии корманд '{$employee->full_name}' имконнопазир: {$conflicts->implode(', ')} аллакай аз ҷониби корманди фаъоли дигар истифода мешавад.");
        }

        $this->restore($employee, "Корманд аз сабад барқарор карда шуд: {$employee->full_name}");
    }

    public function forceDeleteEmployee(Employee $employee): void
    {
        $this->purge($employee, "Корманд аз сабад тамоман нест карда шуд: {$employee->full_name}");
    }

    public function restoreBranch(Branch $branch): void
    {
        if (Branch::where('code', $branch->code)->whereKeyNot($branch->id)->exists()) {
            throw new TrashConflictException("Барқарорсозии филиал '{$branch->name}' имконнопазир: рамзи '{$branch->code}' аллакай аз ҷониби филиали фаъол истифода мешавад.");
        }

        $this->restore($branch, "Филиал аз сабад барқарор карда шуд: {$branch->name}");
    }

    public function forceDeleteBranch(Branch $branch): void
    {
        $employeeCount = Employee::withTrashed()->where('branch_id', $branch->id)->count();
        if ($employeeCount > 0) {
            throw new TrashConflictException("Филиали '{$branch->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он кормандон вобаста шудаанд ({$employeeCount} нафар). Аввал онҳоро интиқол диҳед ё нест кунед.");
        }

        $departmentCount = Department::withTrashed()->where('branch_id', $branch->id)->count();
        $vacancyCount = Vacancy::withTrashed()->where('branch_id', $branch->id)->count();
        if ($departmentCount > 0 || $vacancyCount > 0) {
            throw new TrashConflictException("Филиали '{$branch->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он шуъбаҳо ({$departmentCount}) ё вакансияҳо ({$vacancyCount}) вобаста шудаанд. Аввал онҳоро нест кунед.");
        }

        $this->purge($branch, "Филиал аз сабад тамоман нест карда шуд: {$branch->name}");
    }

    public function restoreDepartment(Department $department): void
    {
        $nameTaken = Department::where('branch_id', $department->branch_id)
            ->where('name', $department->name)
            ->where(function ($q) use ($department) {
                $department->parent_id === null
                    ? $q->whereNull('parent_id')
                    : $q->where('parent_id', $department->parent_id);
            })
            ->whereKeyNot($department->id)
            ->exists();

        $externalTaken = $department->external_id !== null
            && Department::where('external_id', $department->external_id)->whereKeyNot($department->id)->exists();

        if ($nameTaken || $externalTaken) {
            throw new TrashConflictException("Барқарорсозии шуъба '{$department->name}' имконнопазир: шуъбаи фаъол бо ҳамин ном аллакай вуҷуд дорад.");
        }

        $this->restore($department, "Шуъба аз сабад барқарор карда шуд: {$department->name}");
    }

    public function forceDeleteDepartment(Department $department): void
    {
        if (Department::withTrashed()->where('parent_id', $department->id)->exists()) {
            throw new TrashConflictException("Шуъбаи '{$department->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он зершуъбаҳо вобаста шудаанд.");
        }

        $this->purge($department, "Шуъба аз сабад тамоман нест карда шуд: {$department->name}");
    }

    public function restoreUser(User $user): void
    {
        $emailTaken = User::whereRaw('LOWER(TRIM(email)) = LOWER(TRIM(?))', [$user->email])
            ->whereKeyNot($user->id)
            ->exists();

        if ($emailTaken) {
            throw new TrashConflictException("Барқарорсозии корбар '{$user->name}' имконнопазир: почтаи '{$user->email}' аллакай аз ҷониби корбари фаъол истифода мешавад.");
        }

        $this->restore($user, "Корбар аз сабад барқарор карда шуд: {$user->name}");
    }

    public function forceDeleteUser(User $user): void
    {
        $this->purge($user, "Корбар аз сабад тамоман нест карда шуд: {$user->name}");
    }

    /**
     * Восстанавливает мягко удалённую запись внутри транзакции, логируя действие
     * без автоматического activity-лога модели (мы пишем явную запись).
     *
     * @param  Employee|Branch|Department|User  $model
     */
    private function restore($model, string $logMessage): void
    {
        DB::transaction(function () use ($model, $logMessage) {
            $model->disableLogging()->restore();

            activity()->performedOn($model)->event('updated')->log($logMessage);
        });
    }

    /**
     * Окончательно удаляет запись внутри транзакции, логируя до исчезновения
     * строки, чтобы запись аудита сохранила свой субъект.
     *
     * @param  Employee|Branch|Department|User  $model
     */
    private function purge($model, string $logMessage): void
    {
        DB::transaction(function () use ($model, $logMessage) {
            activity()->performedOn($model)->event('deleted')->log($logMessage);

            $model->disableLogging()->forceDelete();
        });
    }
}
