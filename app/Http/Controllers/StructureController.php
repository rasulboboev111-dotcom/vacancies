<?php

namespace App\Http\Controllers;

use App\Data\DepartmentListItemData;
use App\Data\DepartmentTreeData;
use App\Enums\VacancyStatus;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StructureController extends Controller
{
    /**
     * Display the organisational structure (branches + departments) as a graph.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $branches = Branch::query()
            ->withCount(['employees' => fn ($q) => $q->whereNull('dismissal_date')])
            ->viewableBy($user)
            ->orderBy('name')
            ->get();

        $departments = collect();
        $vacancyCounts = collect();

        if ($branches->isNotEmpty()) {
            $branchIds = $branches->pluck('id');

            $departments = Department::query()
                ->whereIn('branch_id', $branchIds)
                ->with('manager:id,full_name')
                ->withCount(['children', 'employees' => fn ($q) => $q->whereNull('dismissal_date')])
                ->orderBy('sort_order')
                ->get();

            $vacancyCounts = Vacancy::query()
                ->whereIn('branch_id', $branchIds)
                ->where('status', VacancyStatus::OPEN->value)
                ->selectRaw('branch_id, department_id, count(*) as total')
                ->groupBy('branch_id', 'department_id')
                ->get();
        }

        $structure = $branches->map(function (Branch $branch) use ($departments, $vacancyCounts) {
            $branchDepartments = $departments->where('branch_id', $branch->id);

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'employees_count' => $branch->employees_count,
                'open_vacancies' => (int) $vacancyCounts->where('branch_id', $branch->id)->sum('total'),
                'departments' => $this->buildTree($branchDepartments, $vacancyCounts, $branch->id, null),
            ];
        })->values();

        return Inertia::render('Structure/Index', [
            'structure' => $structure,
            'branches' => $branches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'employees_count' => $branch->employees_count,
            ])->values(),
            'departmentsFlat' => DepartmentListItemData::collect($departments->values()),
        ]);
    }

    /**
     * Return the employees that belong directly to a branch but are not
     * assigned to any department (department_id IS NULL), so they are not
     * duplicated by the per-department popups.
     */
    public function branchEmployees(Request $request, Branch $branch): JsonResponse
    {
        $user = $request->user();

        // Non-admins may only inspect their own branch.
        if (! $user->hasRole('Admin')) {
            if ($user->branch_id === null || $branch->id !== $user->branch_id) {
                abort(403);
            }
        }

        // The branch (company) manager is its CEO, referenced by source id.
        $manager = $branch->ceo_external_id
            ? Employee::query()
                ->where('branch_id', $branch->id)
                ->where('external_id', $branch->ceo_external_id)
                ->first(['id', 'full_name', 'phone_number'])
            : null;

        $employees = Employee::query()
            ->select(['id', 'full_name', 'phone_number', 'category'])
            ->where('branch_id', $branch->id)
            ->whereNull('department_id')
            ->whereNull('dismissal_date')
            ->when($manager, fn ($q) => $q->where('id', '!=', $manager->id))
            ->orderBy('sort_order')
            ->orderBy('full_name')
            ->get()
            ->map($this->presentEmployee(...));

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
            ],
            'manager' => $manager ? [
                'id' => $manager->id,
                'full_name' => $manager->full_name,
                'phone_number' => $manager->phone_number,
            ] : null,
            'employees' => $employees,
        ]);
    }

    /**
     * Return the employees that belong directly to a single department
     * (excluding nested sub-departments) for the structure tree popup.
     */
    public function departmentEmployees(Request $request, Department $department): JsonResponse
    {
        $user = $request->user();

        // Non-admins may only inspect departments of their own branch.
        if (! $user->hasRole('Admin')) {
            if ($user->branch_id === null || $department->branch_id !== $user->branch_id) {
                abort(403);
            }
        }

        $department->loadMissing('manager:id,full_name,phone_number');

        $employees = Employee::query()
            ->select(['id', 'full_name', 'phone_number', 'category'])
            ->where('department_id', $department->id)
            ->whereNull('dismissal_date')
            ->when($department->manager_id, fn ($q) => $q->where('id', '!=', $department->manager_id))
            ->orderBy('sort_order')
            ->orderBy('full_name')
            ->get()
            ->map($this->presentEmployee(...));

        return response()->json([
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
            'manager' => $department->manager ? [
                'id' => $department->manager->id,
                'full_name' => $department->manager->full_name,
                'phone_number' => $department->manager->phone_number,
            ] : null,
            'employees' => $employees,
        ]);
    }

    /**
     * Shape an employee for the structure popup, exposing the derived
     * is_manager flag (true when the employee's category is "Роҳбарият").
     *
     * @return array<string, mixed>
     */
    private function presentEmployee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'full_name' => $employee->full_name,
            'phone_number' => $employee->phone_number,
            'is_manager' => $employee->is_manager,
        ];
    }

    /**
     * Recursively build the department tree with employee and open-vacancy counts.
     *
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, object>  $vacancyCounts
     * @return list<DepartmentTreeData>
     */
    private function buildTree($departments, $vacancyCounts, int $branchId, ?int $parentId): array
    {
        return Department::sortBySource($departments->where('parent_id', $parentId))
            ->map(fn (Department $department) => new DepartmentTreeData(
                id: $department->id,
                name: $department->name,
                short_name: $department->short_name,
                code: $department->code,
                manager_id: $department->manager_id,
                manager_name: $department->manager?->full_name,
                employees_count: $department->employees_count,
                open_vacancies: (int) $vacancyCounts
                    ->where('branch_id', $branchId)
                    ->where('department_id', $department->id)
                    ->sum('total'),
                children: $this->buildTree($departments, $vacancyCounts, $branchId, $department->id),
            ))
            ->all();
    }
}
