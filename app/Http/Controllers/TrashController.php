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
     * Отображает список мягко удалённых ресурсов.
     */
    public function index(): Response
    {
        // Корзину открывает только суперадмин (can:access-trash), поэтому ничего
        // не скоупим по филиалу — показываем все мягко удалённые ресурсы.
        return Inertia::render('Trash/Index', [
            'employees' => Employee::onlyTrashed()->with(['branch', 'position', 'manager'])->latest('deleted_at')->get(),
            'branches' => Branch::onlyTrashed()->latest('deleted_at')->get(),
            'users' => User::onlyTrashed()->with('branch')->latest('deleted_at')->get(),
            'departments' => Department::onlyTrashed()->with('branch')->latest('deleted_at')->get(),
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
