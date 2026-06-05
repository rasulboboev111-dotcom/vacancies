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
use Illuminate\Http\JsonResponse;
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

        // Eager-load only the columns the list/view/edit actually read (the
        // table & dialogs use branch/department/position name and manager
        // full_name), instead of hydrating four full related models per row.
        $base = Employee::with([
            'branch:id,name',
            'department:id,name',
            'position:id,name',
            'manager:id,full_name',
            // Lookups behind the appended nationality/education/specialty/
            // birth_place accessors — eager-loaded so serializing the page
            // doesn't fire 4 lazy queries per row.
            'nationalityRef:id,name',
            'educationRef:id,name',
            'specialtyRef:id,name',
            'birthPlaceRef:id,name',
        ])
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
     * Search employees for the "direct manager" picker (server-side, capped),
     * so the form never has to ship the entire workforce. Scoped to the user's
     * branch for non-admins, mirroring the employees they may manage.
     */
    public function managers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();
        $term = trim((string) $request->input('search', ''));

        $managers = Employee::query()
            ->active()
            ->when(! $user->hasRole('Admin'), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($term !== '', fn ($q) => $q->where('full_name', 'like', "%{$term}%"))
            ->orderBy('full_name')
            ->limit(20)
            ->get(['id', 'full_name']);

        return response()->json($managers);
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
    public function update(UpdateEmployeeRequest $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        $this->employees->update($employee, $request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        Gate::authorize('delete', $employee);

        $this->employees->delete($employee);

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият нест карда шуд.');
    }

    /**
     * Reinstate a dismissed (archived) employee back to the active roster.
     */
    public function restore(int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

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

        $base = Employee::with([
            'branch', 'department', 'position', 'manager',
            'nationalityRef:id,name', 'educationRef:id,name', 'specialtyRef:id,name', 'birthPlaceRef:id,name',
        ])
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
    public function rotate(RotateEmployeeRequest $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

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
     * Wipe the whole rotation history. Admin-only and irreversible — mirrors the
     * activity-log clear action.
     */
    public function clearRotations(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        Rotation::query()->delete();

        return back()->with('success', 'Таърихи ҷобаҷогузорӣ пурра тоза карда шуд.');
    }

    /**
     * Reference vocabularies for the employees screen.
     *
     * Only branches/departments/types are needed for the toolbar filters on
     * first paint, so they load immediately. Everything else is consumed solely
     * by the create/edit & rotation dialogs, so it is deferred (Inertia group
     * "form"): the list renders first and the form data streams in afterwards,
     * and it is skipped on filter/pagination partial reloads (`only`). A user
     * without a branch (and not an admin) may not manage employees → empty lists.
     *
     * @return array<string, mixed>
     */
    private function referenceData($user): array
    {
        $isAdmin = $user->hasRole('Admin');
        $canManage = $isAdmin || $user->branch_id !== null;
        $branchId = $user->branch_id;

        return [
            // Immediate — required by the toolbar filters.
            'branches' => $canManage ? Branch::orderBy('name')->get() : collect(),
            'departments' => $canManage
                ? Department::query()
                    ->when(! $isAdmin, fn ($q) => $q->where('branch_id', $branchId))
                    ->orderBy('name')
                    ->get(['id', 'branch_id', 'name', 'code'])
                : collect(),
            'types' => collect(EmploymentType::cases())->map(fn (EmploymentType $t) => [
                'id' => $t->value,
                'name' => $t->label(),
            ]),

            // Deferred (group "form") — only the dialogs consume these, so they
            // never bloat the list's initial load or its partial reloads.
            'categories' => Inertia::defer(fn () => collect(Category::cases())->map(fn (Category $c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ]), 'form'),
            'positions' => Inertia::defer(fn () => $canManage ? Position::orderBy('name')->get() : collect(), 'form'),
            // managers are fetched on demand via the searchable endpoint
            // (employees.managers) — never shipped as a full list.
            'nationalities' => Inertia::defer(fn () => Nationality::orderBy('name')->pluck('name'), 'form'),
            'educations' => Inertia::defer(fn () => Education::orderBy('name')->pluck('name'), 'form'),
            'specialties' => Inertia::defer(fn () => Specialty::orderBy('name')->pluck('name'), 'form'),
            'birthPlaces' => Inertia::defer(fn () => BirthPlace::orderBy('name')->pluck('name'), 'form'),
        ];
    }
}
