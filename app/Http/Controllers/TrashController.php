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
        $employeesQuery = Employee::onlyTrashed()->with(['branch', 'category', 'position', 'structure', 'manager']);
        // Build query for users
        $usersQuery = User::onlyTrashed()->with('branch');
        // Build query for branches
        $branchesQuery = Branch::onlyTrashed();

        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null) {
                $employeesQuery->whereRaw('1=0');
                $branchesQuery->whereRaw('1=0');
            }
            $usersQuery->whereRaw('1=0');
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
        if (!$user->can('delete employees')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        $employee = Employee::onlyTrashed()->findOrFail($id);

        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null || $employee->branch_id != $user->branch_id) {
                abort(403, 'Шумо метавонед танҳо кормандони филиали худро барқарор кунед.');
            }
        }

        $employee->restore();

        return redirect()->back()->with('success', "Корманд '{$employee->full_name}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted employee.
     */
    public function forceDeleteEmployee(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->can('delete employees')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        $employee = Employee::onlyTrashed()->findOrFail($id);

        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null || $employee->branch_id != $user->branch_id) {
                abort(403, 'Шумо метавонед кормандони танҳо филиали худро нест кунед.');
            }
        }

        $fullName = $employee->full_name;
        $employee->forceDelete();

        return redirect()->back()->with('success', "Корманд '{$fullName}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    /**
     * Restore the specified soft-deleted branch.
     */
    public function restoreBranch(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            abort(403, 'Шумо барои барқарор кардани филиалҳо ҳуқуқ надоред.');
        }

        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->restore();

        return redirect()->back()->with('success', "Филиал '{$branch->name}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted branch.
     */
    public function forceDeleteBranch(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            abort(403, 'Шумо барои нест кардани қатъии филиалҳо ҳуқуқ надоред.');
        }

        $branch = Branch::onlyTrashed()->findOrFail($id);

        // Safety check: Prevent force deleting branch if active or soft-deleted employees are linked to it
        $employeeCount = Employee::withTrashed()->where('branch_id', $id)->count();
        if ($employeeCount > 0) {
            return redirect()->back()->with('error', "Филиали '{$branch->name}'-ро ба таври қатъӣ нест кардан мумкин нест, зеро ба он кормандон вобаста шудаанд ({$employeeCount} нафар). Аввал онҳоро интиқол диҳед ё нест кунед.");
        }

        $name = $branch->name;
        $branch->forceDelete();

        return redirect()->back()->with('success', "Филиал '{$name}' аз пойгоҳи додаҳо ба таври қатъӣ нест карда шуд.");
    }

    public function restoreUser(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        $targetUser = User::onlyTrashed()->findOrFail($id);
        $targetUser->restore();

        return redirect()->back()->with('success', "Корбар '{$targetUser->name}' бомуваффақият барқарор карда шуд.");
    }

    /**
     * Permanently delete the specified soft-deleted user.
     */
    public function forceDeleteUser(Request $request, $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            abort(403, 'Шумо барои нест кардани қатъии корбарон ҳуқуқ надоред.');
        }

        $targetUser = User::onlyTrashed()->findOrFail($id);

        if ($user->id === $targetUser->id) {
            return redirect()->back()->with('error', 'Шумо наметавонед аккаунти худро ба таври қатъӣ нест кунед.');
        }

        $name = $targetUser->name;
        $targetUser->forceDelete();

        return redirect()->back()->with('success', "Корбар '{$name}' аз низом ба таври қатъӣ нест карда шуд.");
    }
}
