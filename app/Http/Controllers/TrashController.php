<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    /**
     * Display a listing of the soft-deleted resources.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Branch users see only their own branch's trashed employees (viewableBy);
        // trashed branches and users are admin-only.
        $employeesQuery = Employee::onlyTrashed()->with(['branch', 'position', 'manager'])->viewableBy($user);
        $usersQuery = User::onlyTrashed()->with('branch');
        $branchesQuery = Branch::onlyTrashed();

        // Departments are branch-scoped: a branch manager sees only their own
        // branch's trashed departments, the same as employees.
        $departmentsQuery = Department::onlyTrashed()->with('branch');

        if (! $user->hasRole('Admin')) {
            $usersQuery->whereRaw('1=0');
            $branchesQuery->whereRaw('1=0');

            if ($user->branch_id === null) {
                $departmentsQuery->whereRaw('1=0');
            } else {
                $departmentsQuery->where('branch_id', $user->branch_id);
            }
        }

        return Inertia::render('Trash/Index', [
            'employees' => $employeesQuery->latest('deleted_at')->get(),
            'branches' => $branchesQuery->latest('deleted_at')->get(),
            'users' => $usersQuery->latest('deleted_at')->get(),
            'departments' => $departmentsQuery->latest('deleted_at')->get(),
        ]);
    }

    /**
     * Restore the specified soft-deleted employee.
     */
    public function restoreEmployee($id): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $employee);

        $fullName = $employee->full_name;

        // While this row sat in the trash, a live employee may have reused one of
        // its unique identifiers (the unique indexes are soft-delete-aware).
        // Restoring would then hit a raw DB unique violation — block with a clear
        // message instead.
        $conflicts = collect([
            'external_id' => 'ID-и берунӣ',
            'inn' => 'РМА (ИНН)',
            'passport_number' => 'рақами шиноснома',
            'sin' => 'РСШ (SIN)',
            'email' => 'почтаи электронӣ',
        ])->filter(fn ($label, $col) => filled($employee->{$col})
            && Employee::where($col, $employee->{$col})->whereKeyNot($employee->id)->exists());

        if ($conflicts->isNotEmpty()) {
            return redirect()->back()->with('error', "Барқарорсозии корманд '{$fullName}' имконнопазир: {$conflicts->implode(', ')} аллакай аз ҷониби корманди фаъоли дигар истифода мешавад.");
        }

        DB::transaction(function () use ($employee, $fullName) {
            $employee->disableLogging()->restore();

            activity()->performedOn($employee)->event('updated')
                ->log("Корманд аз сабад барқарор карда шуд: {$fullName}");
        });

        return redirect()->back()->with('success', "Корманд '{$fullName}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted employee.
     */
    public function forceDeleteEmployee($id): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $employee);

        $fullName = $employee->full_name;

        DB::transaction(function () use ($employee, $fullName) {
            activity()->performedOn($employee)->event('deleted')
                ->log("Корманд аз сабад тамоман нест карда шуд: {$fullName}");

            $employee->disableLogging()->forceDelete();
        });

        return redirect()->back()->with('success', "Корманд '{$fullName}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    /**
     * Restore the specified soft-deleted branch.
     */
    public function restoreBranch($id): RedirectResponse
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $branch);

        // The code unique index is soft-delete-aware, so a live branch may have
        // taken this code while it was trashed. Restoring would collide.
        if (Branch::where('code', $branch->code)->whereKeyNot($branch->id)->exists()) {
            return redirect()->back()->with('error', "Барқарорсозии филиал '{$branch->name}' имконнопазир: рамзи '{$branch->code}' аллакай аз ҷониби филиали фаъол истифода мешавад.");
        }

        DB::transaction(function () use ($branch) {
            $branch->disableLogging()->restore();

            activity()->performedOn($branch)->event('updated')
                ->log("Филиал аз сабад барқарор карда шуд: {$branch->name}");
        });

        return redirect()->back()->with('success', "Филиал '{$branch->name}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted branch.
     */
    public function forceDeleteBranch($id): RedirectResponse
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $branch);

        // Safety check: the branch_id foreign keys on employees, departments and
        // vacancies are RESTRICT, so a force-delete with any (even soft-deleted)
        // dependant would raise a raw DB error. Surface a clear message instead.
        $employeeCount = Employee::withTrashed()->where('branch_id', $id)->count();
        if ($employeeCount > 0) {
            return redirect()->back()->with('error', "Филиали '{$branch->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он кормандон вобаста шудаанд ({$employeeCount} нафар). Аввал онҳоро интиқол диҳед ё нест кунед.");
        }

        $departmentCount = Department::withTrashed()->where('branch_id', $id)->count();
        $vacancyCount = Vacancy::withTrashed()->where('branch_id', $id)->count();
        if ($departmentCount > 0 || $vacancyCount > 0) {
            return redirect()->back()->with('error', "Филиали '{$branch->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он шуъбаҳо ({$departmentCount}) ё вакансияҳо ({$vacancyCount}) вобаста шудаанд. Аввал онҳоро нест кунед.");
        }

        $name = $branch->name;

        DB::transaction(function () use ($branch, $name) {
            activity()->performedOn($branch)->event('deleted')
                ->log("Филиал аз сабад тамоман нест карда шуд: {$name}");

            $branch->disableLogging()->forceDelete();
        });

        return redirect()->back()->with('success', "Филиал '{$name}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    /**
     * Restore the specified soft-deleted department.
     */
    public function restoreDepartment($id): RedirectResponse
    {
        $department = Department::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $department);

        // The (branch, parent, name) and external_id unique indexes are
        // soft-delete-aware, so a live department may have taken this slot while
        // the row sat in the trash. Restoring would collide — block clearly.
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
            return redirect()->back()->with('error', "Барқарорсозии шуъба '{$department->name}' имконнопазир: шуъбаи фаъол бо ҳамин ном аллакай вуҷуд дорад.");
        }

        DB::transaction(function () use ($department) {
            $department->disableLogging()->restore();

            activity()->performedOn($department)->event('updated')
                ->log("Шуъба аз сабад барқарор карда шуд: {$department->name}");
        });

        return redirect()->back()->with('success', "Шуъба '{$department->name}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted department.
     */
    public function forceDeleteDepartment($id): RedirectResponse
    {
        $department = Department::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $department);

        // A trashed department is always a leaf (delete blocks while children
        // exist) and its employee/vacancy references were nulled on soft-delete.
        // Guard defensively against any lingering child before the hard delete.
        if (Department::withTrashed()->where('parent_id', $department->id)->exists()) {
            return redirect()->back()->with('error', "Шуъбаи '{$department->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он зершуъбаҳо вобаста шудаанд.");
        }

        $name = $department->name;

        DB::transaction(function () use ($department, $name) {
            activity()->performedOn($department)->event('deleted')
                ->log("Шуъба аз сабад тамоман нест карда шуд: {$name}");

            $department->disableLogging()->forceDelete();
        });

        return redirect()->back()->with('success', "Шуъба '{$name}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    /**
     * Restore the specified soft-deleted user.
     */
    public function restoreUser($id): RedirectResponse
    {
        $targetUser = User::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $targetUser);

        // users.email has a case-insensitive soft-delete-aware unique index, so a
        // live user may have taken this email while it was trashed.
        $emailTaken = User::whereRaw('LOWER(TRIM(email)) = LOWER(TRIM(?))', [$targetUser->email])
            ->whereKeyNot($targetUser->id)
            ->exists();

        if ($emailTaken) {
            return redirect()->back()->with('error', "Барқарорсозии корбар '{$targetUser->name}' имконнопазир: почтаи '{$targetUser->email}' аллакай аз ҷониби корбари фаъол истифода мешавад.");
        }

        DB::transaction(function () use ($targetUser) {
            $targetUser->disableLogging()->restore();

            activity()->performedOn($targetUser)->event('updated')
                ->log("Корбар аз сабад барқарор карда шуд: {$targetUser->name}");
        });

        return redirect()->back()->with('success', "Корбар '{$targetUser->name}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted user.
     */
    public function forceDeleteUser(Request $request, $id): RedirectResponse
    {
        $targetUser = User::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $targetUser);

        if ($request->user()->id === $targetUser->id) {
            return redirect()->back()->with('error', 'Шумо наметавонед аккаунти худро ба таври қатъӣ нест кунед.');
        }

        $name = $targetUser->name;

        DB::transaction(function () use ($targetUser, $name) {
            activity()->performedOn($targetUser)->event('deleted')
                ->log("Корбар аз сабад тамоман нест карда шуд: {$name}");

            $targetUser->disableLogging()->forceDelete();
        });

        return redirect()->back()->with('success', "Корбар '{$name}' аз низом ба таври қатъӣ нест карда шуд.");
    }
}
