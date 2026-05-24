<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Employee::with('branch');

        // Scoping for Branch Manager: only show employees of their branch
        if ($user->hasRole('Branch Manager')) {
            $branchId = $user->branch_id;
            $query->where('branch_id', $branchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        // Searching
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('inn', 'like', "%{$search}%");
            });
        }

        // Filtering by category
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filtering by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Get employees with pagination
        $employees = $query->latest()->paginate(10)->withQueryString();

        // Get branches list for filters / forms
        $branchesQuery = Branch::query();
        if ($user->hasRole('Branch Manager')) {
            $branchesQuery->where('id', $user->branch_id);
        }
        $branches = $branchesQuery->orderBy('name')->get();

        // Get unique categories and types for filters
        $categories = Employee::distinct()->pluck('category')->filter()->values();
        $types = Employee::distinct()->pluck('type')->filter()->values();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'branches' => $branches,
            'categories' => $categories,
            'types' => $types,
            'filters' => $request->only(['search', 'branch_id', 'category', 'type']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'category' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'structure' => 'required|string|max:255',
            'direct_manager' => 'nullable|string|max:255',
            'hire_date' => 'required|date',
            'dismissal_date' => 'nullable|date|after_or_equal:hire_date',
            'passport_issued_by' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:50',
        ]);

        // Branch Manager can only add employees to their own branch
        if ($user->hasRole('Branch Manager') && $validated['branch_id'] != $user->branch_id) {
            abort(403, 'Вы можете добавлять сотрудников только в свой филиал.');
        }

        $employee = Employee::create($validated);

        activity()
            ->performedOn($employee)
            ->event('created')
            ->log("Добавлен сотрудник: {$employee->full_name} на должность {$employee->position}");

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно добавлен.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $user = $request->user();

        // Branch Manager can only update employees of their own branch
        if ($user->hasRole('Branch Manager') && $employee->branch_id != $user->branch_id) {
            abort(403, 'Вы можете редактировать сотрудников только своего филиала.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'category' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'structure' => 'required|string|max:255',
            'direct_manager' => 'nullable|string|max:255',
            'hire_date' => 'required|date',
            'dismissal_date' => 'nullable|date|after_or_equal:hire_date',
            'passport_issued_by' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:50',
        ]);

        // Branch Manager cannot transfer employee to another branch
        if ($user->hasRole('Branch Manager') && $validated['branch_id'] != $user->branch_id) {
            abort(403, 'Вы не можете переводить сотрудников в другой филиал.');
        }

        $employee->update($validated);

        activity()
            ->performedOn($employee)
            ->event('updated')
            ->log("Обновлена информация о сотруднике: {$employee->full_name}");

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно обновлен.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        $user = $request->user();

        // Branch Manager can only delete employees of their own branch
        if ($user->hasRole('Branch Manager') && $employee->branch_id != $user->branch_id) {
            abort(403, 'Вы можете удалять сотрудников только своего филиала.');
        }

        $fullName = $employee->full_name;
        $employee->delete();

        activity()
            ->event('deleted')
            ->log("Удален сотрудник: {$fullName}");

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно удален.');
    }
}
