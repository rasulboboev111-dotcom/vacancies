<?php

namespace App\Http\Controllers;

use App\Exceptions\TrashConflictException;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\TrashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    public function __construct(private readonly TrashService $trash) {}

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

        if (! $user->isAdmin()) {
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

    public function restoreEmployee(int $id): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $employee);

        try {
            $this->trash->restoreEmployee($employee);
        } catch (TrashConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Корманд '{$employee->full_name}' бомуваффақият барқарор карда шуд.");
    }

    public function forceDeleteEmployee(int $id): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $employee);

        $this->trash->forceDeleteEmployee($employee);

        return back()->with('success', "Корманд '{$employee->full_name}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    public function restoreBranch(int $id): RedirectResponse
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $branch);

        try {
            $this->trash->restoreBranch($branch);
        } catch (TrashConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Филиал '{$branch->name}' бомуваффақият барқарор карда шуд.");
    }

    public function forceDeleteBranch(int $id): RedirectResponse
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $branch);

        try {
            $this->trash->forceDeleteBranch($branch);
        } catch (TrashConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Филиал '{$branch->name}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    public function restoreDepartment(int $id): RedirectResponse
    {
        $department = Department::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $department);

        try {
            $this->trash->restoreDepartment($department);
        } catch (TrashConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Шуъба '{$department->name}' бомуваффақият барқарор карда шуд.");
    }

    public function forceDeleteDepartment(int $id): RedirectResponse
    {
        $department = Department::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $department);

        try {
            $this->trash->forceDeleteDepartment($department);
        } catch (TrashConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Шуъба '{$department->name}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    public function restoreUser(int $id): RedirectResponse
    {
        $targetUser = User::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $targetUser);

        try {
            $this->trash->restoreUser($targetUser);
        } catch (TrashConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Корбар '{$targetUser->name}' бомуваффақият барқарор карда шуд.");
    }

    public function forceDeleteUser(Request $request, int $id): RedirectResponse
    {
        $targetUser = User::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $targetUser);

        if ($request->user()->id === $targetUser->id) {
            return back()->with('error', 'Шумо наметавонед аккаунти худро ба таври қатъӣ нест кунед.');
        }

        $this->trash->forceDeleteUser($targetUser);

        return back()->with('success', "Корбар '{$targetUser->name}' аз низом ба таври қатъӣ нест карда шуд.");
    }
}
