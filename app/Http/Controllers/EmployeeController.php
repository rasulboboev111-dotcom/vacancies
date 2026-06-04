<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\EmploymentType;
use App\Http\Requests\Employee\RotateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\BirthPlace;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Nationality;
use App\Models\Position;
use App\Models\Rotation;
use App\Models\Specialty;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employees) {}

    /**
     * Display a listing of the active employees.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $base = Employee::with(['branch', 'department', 'position', 'manager'])
            ->active()
            ->viewableBy($user)
            ->latest()
            ->latest('id');

        $employees = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('department_id'),
                AllowedFilter::exact('type_id', 'employment_type'),
            ])
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Employees/Index', array_merge(
            ['employees' => $employees],
            $this->referenceData($user),
            ['filters' => $request->input('filter', [])],
        ));
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employees->create($request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият илова шуд.');
    }

    /**
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employees->update($employee, $request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);

        $this->employees->delete($employee);

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият нест карда шуд.');
    }

    /**
     * Reinstate a dismissed (archived) employee back to the active roster.
     */
    public function restore(Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $this->employees->reinstate($employee);

        return redirect()->route('employees.archive')
            ->with('success', 'Корманд аз бойгонӣ бомуваффақият барқарор карда шуд.');
    }

    /**
     * Display a listing of the dismissed/archived employees.
     */
    public function archive(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $base = Employee::with(['branch', 'department', 'position', 'manager'])
            ->dismissed()
            ->viewableBy($user)
            ->latest('dismissal_date')
            ->latest('id');

        $employees = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::exact('branch_id'),
            ])
            ->paginate(10)
            ->withQueryString();

        $branches = $user->branch_id !== null || $user->hasRole('Admin')
            ? Branch::orderBy('name')->get()
            : collect();

        return Inertia::render('Employees/Archive', [
            'employees' => $employees,
            'branches' => $branches,
            'filters' => $request->input('filter', []),
        ]);
    }

    /**
     * Rotate the specified employee to a new branch, position, or department.
     */
    public function rotate(RotateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->employees->rotate($employee, $request->validated());

        return redirect()->back()
            ->with('success', 'Ротатсияи корманд бомуваффақият анҷом дода шуд.');
    }

    /**
     * Display a timeline/list of all rotations.
     */
    public function rotationsIndex(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $rotations = Rotation::with(['employee', 'oldBranch', 'newBranch', 'oldPosition', 'newPosition', 'oldDepartment', 'newDepartment'])
            ->when(! $user->hasRole('Admin'), function ($q) use ($user) {
                // Branch users see only rotations into or out of their own branch.
                if ($user->branch_id === null) {
                    $q->whereRaw('1=0');
                } else {
                    $q->where(fn ($sub) => $sub->where('new_branch_id', $user->branch_id)
                        ->orWhere('old_branch_id', $user->branch_id));
                }
            })
            ->latest('rotation_date')
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Rotations/Index', [
            'rotations' => $rotations,
        ]);
    }

    /**
     * Reference vocabularies for the create/edit form. A user without a branch
     * (and not an admin) gets empty lists since they may not manage employees.
     *
     * @return array<string, mixed>
     */
    private function referenceData($user): array
    {
        if (! $user->hasRole('Admin') && $user->branch_id === null) {
            return [
                'branches' => collect(), 'categories' => collect(), 'types' => collect(),
                'positions' => collect(), 'departments' => collect(), 'managers' => collect(),
                'nationalities' => collect(), 'educations' => collect(),
                'specialties' => collect(), 'birthPlaces' => collect(),
            ];
        }

        $departmentsQuery = Department::query()->orderBy('name');
        $managersQuery = Employee::query()->orderBy('full_name');

        if (! $user->hasRole('Admin')) {
            $departmentsQuery->where('branch_id', $user->branch_id);
            $managersQuery->where('branch_id', $user->branch_id);
        }

        return [
            'branches' => Branch::orderBy('name')->get(),
            'categories' => collect(Category::cases())->map(fn (Category $c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
            'types' => collect(EmploymentType::cases())->map(fn (EmploymentType $t) => [
                'id' => $t->value,
                'name' => $t->label(),
            ]),
            'positions' => Position::orderBy('name')->get(),
            'departments' => $departmentsQuery->get(['id', 'branch_id', 'name', 'code']),
            'managers' => $managersQuery->get(['id', 'full_name']),
            'nationalities' => Nationality::orderBy('name')->pluck('name'),
            'educations' => Education::orderBy('name')->pluck('name'),
            'specialties' => Specialty::orderBy('name')->pluck('name'),
            'birthPlaces' => BirthPlace::orderBy('name')->pluck('name'),
        ];
    }
}
