<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Rotation;
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
        \Illuminate\Support\Facades\Gate::authorize('viewAny', Employee::class);

        $user = $request->user();
        $query = Employee::with(['branch', 'category', 'position', 'structure', 'manager'])->whereNull('dismissal_date');

        if ($user->branch_id === null && !$user->hasRole('Admin')) {
            $query->whereRaw('1=0');
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        // Searching
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhereHas('position', function ($posQ) use ($search) {
                      $posQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhere('inn', 'like', "%{$search}%");
            });
        }

        // Filtering by category_id
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filtering by type_id
        if ($request->filled('type_id')) {
            $query->where('employment_type', $request->input('type_id'));
        }

        // Get employees with pagination
        $employees = $query->latest()->paginate(10)->withQueryString();

        $branches = $user->branch_id !== null || $user->hasRole('Admin')
            ? Branch::orderBy('name')->get()
            : collect();

        // Get lookup tables
        if (!$user->hasRole('Admin') && $user->branch_id === null) {
            $categories = collect();
            $types = collect();
            $positions = collect();
            $structures = collect();
            $managers = collect();
        } else {
            $categories = \App\Models\Category::orderBy('name')->get();
            $types = collect(\App\Enums\EmploymentType::cases())->map(fn($t) => [
                'id' => $t->value,
                'name' => $t->label()
            ]);
            $positions = \App\Models\Position::orderBy('name')->get();
            $structures = \App\Models\Structure::orderBy('name')->get();
            
            if (!$user->hasRole('Admin')) {
                $managers = Employee::where('branch_id', $user->branch_id)->orderBy('full_name')->get(['id', 'full_name']);
            } else {
                $managers = Employee::orderBy('full_name')->get(['id', 'full_name']);
            }
        }

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'branches' => $branches,
            'categories' => $categories,
            'types' => $types,
            'positions' => $positions,
            'structures' => $structures,
            'managers' => $managers,
            'filters' => $request->only(['search', 'branch_id', 'category_id', 'type_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Employee::class);

        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'category_id' => 'required|exists:categories,id',
            'type_id' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\EmploymentType::class)],
            'full_name' => 'required|string|max:255',
            'gender' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\Gender::class)],
            'position_id' => 'required|exists:positions,id',
            'structure_id' => 'required|exists:structures,id',
            'manager_id' => 'nullable|exists:employees,id',
            'hire_date' => 'required|date',
            'dismissal_date' => 'nullable|date|after_or_equal:hire_date',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'passport_start_date' => 'nullable|date',
            'passport_end_date' => 'nullable|date',
            'passport_issued_by' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:50',
            'sin' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'employment_start_date' => 'nullable|date',
        ]);

        // Non-admin can only add employees to their own branch
        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null) {
                abort(403, 'У вас нет прав для добавления сотрудников.');
            }
            if ($validated['branch_id'] != $user->branch_id) {
                abort(403, 'Вы можете добавлять сотрудников только в свой филиал.');
            }
        }

        $validated['employment_type'] = $validated['type_id'];
        unset($validated['type_id']);

        $employee = Employee::create($validated);
        $employee->load('position');

        activity()
            ->performedOn($employee)
            ->event('created')
            ->log("Добавлен сотрудник: {$employee->full_name} на должность " . ($employee->position?->name ?? 'Неизвестная должность'));

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно добавлен.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $employee);

        $user = $request->user();

        // Non-admin can only update employees of their own branch
        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null || $employee->branch_id != $user->branch_id) {
                abort(403, 'Вы можете редактировать сотрудников только своего филиала.');
            }
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'category_id' => 'required|exists:categories,id',
            'type_id' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\EmploymentType::class)],
            'full_name' => 'required|string|max:255',
            'gender' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\Gender::class)],
            'position_id' => 'required|exists:positions,id',
            'structure_id' => 'required|exists:structures,id',
            'manager_id' => 'nullable|exists:employees,id',
            'hire_date' => 'required|date',
            'dismissal_date' => 'nullable|date|after_or_equal:hire_date',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'passport_number' => 'nullable|string|max:255',
            'passport_start_date' => 'nullable|date',
            'passport_end_date' => 'nullable|date',
            'passport_issued_by' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:50',
            'sin' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'birth_place' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'employment_start_date' => 'nullable|date',
        ]);

        // Non-admin cannot transfer employee to another branch
        if (!$user->hasRole('Admin') && $validated['branch_id'] != $user->branch_id) {
            abort(403, 'Вы не можете переводить сотрудников в другой филиал.');
        }

        $validated['employment_type'] = $validated['type_id'];
        unset($validated['type_id']);

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
        \Illuminate\Support\Facades\Gate::authorize('delete', $employee);

        $user = $request->user();

        // Non-admin can only delete employees of their own branch
        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null || $employee->branch_id != $user->branch_id) {
                abort(403, 'Вы можете удалять сотрудников только своего филиала.');
            }
        }

        $fullName = $employee->full_name;
        $employee->delete();

        activity()
            ->event('deleted')
            ->log("Удален сотрудник: {$fullName}");

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно удален.');
    }

    /**
     * Display a listing of the dismissed/archived employees.
     */
    public function archive(Request $request): Response
    {
        \Illuminate\Support\Facades\Gate::authorize('viewAny', Employee::class);

        $user = $request->user();
        $query = Employee::with(['branch', 'category', 'position', 'structure', 'manager'])->whereNotNull('dismissal_date');

        if ($user->branch_id === null && !$user->hasRole('Admin')) {
            $query->whereRaw('1=0');
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        // Searching
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhereHas('position', function ($posQ) use ($search) {
                      $posQ->where('name', 'like', "%{$search}%");
                  })
                  ->orWhere('inn', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest('dismissal_date')->paginate(10)->withQueryString();

        $branches = $user->branch_id !== null || $user->hasRole('Admin')
            ? Branch::orderBy('name')->get()
            : collect();

        return Inertia::render('Employees/Archive', [
            'employees' => $employees,
            'branches' => $branches,
            'filters' => $request->only(['search', 'branch_id']),
        ]);
    }

    /**
     * Rotate the specified employee to a new branch, position, or structure.
     */
    public function rotate(Request $request, Employee $employee): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $employee);

        $user = $request->user();

        // Non-admin can only rotate employees of their own branch
        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null || $employee->branch_id != $user->branch_id) {
                abort(403, 'Вы можете проводить ротацию только сотрудников своего филиала.');
            }
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'position_id' => 'required|exists:positions,id',
            'structure_id' => 'required|exists:structures,id',
            'rotation_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Non-admin cannot rotate employee to another branch
        if (!$user->hasRole('Admin') && $validated['branch_id'] != $user->branch_id) {
            abort(403, 'Вы не можете переводить сотрудников в другой филиал.');
        }

        // Save rotation log
        Rotation::create([
            'employee_id' => $employee->id,
            'old_branch_id' => $employee->branch_id,
            'new_branch_id' => $validated['branch_id'],
            'old_position_id' => $employee->position_id,
            'new_position_id' => $validated['position_id'],
            'old_structure_id' => $employee->structure_id,
            'new_structure_id' => $validated['structure_id'],
            'rotation_date' => $validated['rotation_date'],
            'reason' => $validated['reason'],
        ]);

        // Get old details for activity logging
        $oldBranchName = $employee->branch?->name ?? 'Неизвестный филиал';
        $oldPosition = $employee->position?->name ?? 'Неизвестная должность';

        // Update employee record
        $employee->update([
            'branch_id' => $validated['branch_id'],
            'position_id' => $validated['position_id'],
            'structure_id' => $validated['structure_id'],
        ]);

        // Log action
        $newBranchName = Branch::find($validated['branch_id'])?->name ?? 'Неизвестный филиал';
        $newPositionName = \App\Models\Position::find($validated['position_id'])?->name ?? 'Неизвестная должность';
        activity()
            ->performedOn($employee)
            ->event('updated')
            ->log("Выполнена ротация сотрудника {$employee->full_name}. Переведен из {$oldBranchName} ({$oldPosition}) в {$newBranchName} ({$newPositionName})");

        return redirect()->back()
            ->with('success', 'Ротация сотрудника успешно проведена.');
    }

    /**
     * Display a timeline/list of all rotations.
     */
    public function rotationsIndex(Request $request): Response
    {
        $user = $request->user();
        $query = Rotation::with(['employee', 'oldBranch', 'newBranch', 'oldPosition', 'newPosition', 'oldStructure', 'newStructure']);

        if ($user->branch_id === null && !$user->hasRole('Admin')) {
            $query->whereRaw('1=0');
        }

        $rotations = $query->latest('rotation_date')->paginate(15);

        return Inertia::render('Rotations/Index', [
            'rotations' => $rotations,
        ]);
    }
}
