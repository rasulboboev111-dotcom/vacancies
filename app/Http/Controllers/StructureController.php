<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Vacancy;
use Illuminate\Http\Request;
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

        $branchesQuery = Branch::query()
            ->withCount('employees')
            ->orderBy('name');

        if (!$user->hasRole('Admin')) {
            if ($user->branch_id !== null) {
                $branchesQuery->where('id', $user->branch_id);
            } else {
                $branchesQuery->whereRaw('1=0');
            }
        }

        $branches = $branchesQuery->get();

        $departments = collect();
        $vacancyCounts = collect();

        if ($branches->isNotEmpty()) {
            $branchIds = $branches->pluck('id');

            $departments = Department::query()
                ->whereIn('branch_id', $branchIds)
                ->withCount(['children', 'employees'])
                ->orderBy('name')
                ->get();

            $vacancyCounts = Vacancy::query()
                ->whereIn('branch_id', $branchIds)
                ->where('status', Vacancy::STATUS_OPEN)
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
            'departmentsFlat' => $departments->map(fn (Department $department) => [
                'id' => $department->id,
                'branch_id' => $department->branch_id,
                'parent_id' => $department->parent_id,
                'name' => $department->name,
                'code' => $department->code,
                'children_count' => $department->children_count,
            ])->values(),
        ]);
    }

    /**
     * Recursively build the department tree with employee and open-vacancy counts.
     *
     * @param  \Illuminate\Support\Collection<int, Department>  $departments
     * @param  \Illuminate\Support\Collection<int, object>  $vacancyCounts
     * @return list<array<string, mixed>>
     */
    private function buildTree($departments, $vacancyCounts, int $branchId, ?int $parentId): array
    {
        return $departments
            ->where('parent_id', $parentId)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (Department $department) use ($departments, $vacancyCounts, $branchId) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'employees_count' => $department->employees_count,
                    'open_vacancies' => (int) $vacancyCounts
                        ->where('branch_id', $branchId)
                        ->where('department_id', $department->id)
                        ->sum('total'),
                    'children' => $this->buildTree($departments, $vacancyCounts, $branchId, $department->id),
                ];
            })
            ->all();
    }
}
