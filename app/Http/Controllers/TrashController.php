<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

        // Build query for employees
        $employeesQuery = Employee::onlyTrashed()->with(['branch', 'category', 'employmentType', 'position', 'structure', 'manager']);
        // Build query for users
        $usersQuery = User::onlyTrashed()->with('branch');
        // Build query for branches
        $branchesQuery = Branch::onlyTrashed();

        // Scope to current branch for Branch Manager
        if ($user->hasRole('Branch Manager')) {
            $employeesQuery->where('branch_id', $user->branch_id);
            $usersQuery->where('branch_id', $user->branch_id);
            $branchesQuery->where('id', $user->branch_id);
        }

        return Inertia::render('Trash/Index', [
            'employees' => $employeesQuery->latest('deleted_at')->get(),
            'branches' => $branchesQuery->latest('deleted_at')->get(),
            'users' => $usersQuery->latest('deleted_at')->get(),
        ]);
    }

    /**
     * Restore the specified soft-deleted employee.
     */
    public function restoreEmployee(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasRole('Viewer')) {
            abort(403, 'Недостаточно прав.');
        }

        $employee = Employee::onlyTrashed()->findOrFail($id);

        if ($user->hasRole('Branch Manager') && $employee->branch_id != $user->branch_id) {
            abort(403, 'Вы можете восстанавливать только сотрудников своего филиала.');
        }

        $employee->restore();

        return redirect()->back()->with('success', "Сотрудник '{$employee->full_name}' успешно восстановлен.");
    }

    /**
     * Permanently delete the specified soft-deleted employee.
     */
    public function forceDeleteEmployee(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasRole('Viewer')) {
            abort(403, 'Недостаточно прав.');
        }

        $employee = Employee::onlyTrashed()->findOrFail($id);

        if ($user->hasRole('Branch Manager') && $employee->branch_id != $user->branch_id) {
            abort(403, 'Вы можете удалять сотрудников только своего филиала.');
        }

        $fullName = $employee->full_name;
        $employee->forceDelete();

        return redirect()->back()->with('success', "Сотрудник '{$fullName}' был окончательно удален из базы данных.");
    }

    /**
     * Restore the specified soft-deleted branch.
     */
    public function restoreBranch(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin') && !$user->hasRole('Admin') && $user->hasRole('Branch Manager')) {
            abort(403, 'У вас нет прав для восстановления филиалов.');
        }

        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->restore();

        return redirect()->back()->with('success', "Филиал '{$branch->name}' успешно восстановлен.");
    }

    /**
     * Permanently delete the specified soft-deleted branch.
     */
    public function forceDeleteBranch(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin') && !$user->hasRole('Admin')) {
            abort(403, 'У вас нет прав для окончательного удаления филиалов.');
        }

        $branch = Branch::onlyTrashed()->findOrFail($id);

        // Safety check: Prevent force deleting branch if active or soft-deleted employees are linked to it
        $employeeCount = Employee::withTrashed()->where('branch_id', $id)->count();
        if ($employeeCount > 0) {
            return redirect()->back()->with('error', "Невозможно окончательно удалить филиал '{$branch->name}', так как к нему привязаны сотрудники ({$employeeCount} чел.). Сначала переместите или удалите их.");
        }

        $name = $branch->name;
        $branch->forceDelete();

        return redirect()->back()->with('success', "Филиал '{$name}' был окончательно удален из базы данных.");
    }

    /**
     * Restore the specified soft-deleted user.
     */
    public function restoreUser(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasRole('Viewer')) {
            abort(403, 'Недостаточно прав.');
        }

        $targetUser = User::onlyTrashed()->findOrFail($id);

        if ($user->hasRole('Branch Manager') && $targetUser->branch_id != $user->branch_id) {
            abort(403, 'Вы можете восстанавливать только пользователей своего филиала.');
        }

        $targetUser->restore();

        return redirect()->back()->with('success', "Пользователь '{$targetUser->name}' успешно восстановлен.");
    }

    /**
     * Permanently delete the specified soft-deleted user.
     */
    public function forceDeleteUser(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Super Admin') && !$user->hasRole('Admin')) {
            abort(403, 'У вас нет прав для окончательного удаления пользователей.');
        }

        $targetUser = User::onlyTrashed()->findOrFail($id);

        if ($user->id === $targetUser->id) {
            return redirect()->back()->with('error', 'Вы не можете удалить окончательно свой собственный аккаунт.');
        }

        $name = $targetUser->name;
        $targetUser->forceDelete();

        return redirect()->back()->with('success', "Пользователь '{$name}' был окончательно удален из системы.");
    }
}
