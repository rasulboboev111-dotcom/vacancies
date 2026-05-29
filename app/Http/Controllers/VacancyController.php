<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Structure;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vacancy::class);

        $user = $request->user();
        $branches = collect();
        $branchId = null;

        if ($user->hasRole('Admin') || $user->branch_id !== null) {
            $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);

            if ($user->hasRole('Admin')) {
                $branchId = $request->integer('branch_id') ?: null;
            } else {
                $branchId = $user->branch_id;
            }
        }

        $status = $request->input('status');
        $status = in_array($status, [Vacancy::STATUS_OPEN, Vacancy::STATUS_CLOSED], true) ? $status : null;

        $vacanciesQuery = Vacancy::query()
            ->with(['branch:id,name,code', 'department:id,name', 'position:id,name', 'structure:id,name', 'creator:id,name']);

        if (!$user->hasRole('Admin')) {
            if ($user->branch_id !== null) {
                $vacanciesQuery->where('branch_id', $user->branch_id);
            } else {
                $vacanciesQuery->whereRaw('1=0');
            }
        } elseif ($branchId) {
            $vacanciesQuery->where('branch_id', $branchId);
        }

        if ($status) {
            $vacanciesQuery->where('status', $status);
        }

        $vacancies = $vacanciesQuery
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->latest('opened_at')
            ->latest('id')
            ->get()
            ->map(fn (Vacancy $vacancy) => [
                'id' => $vacancy->id,
                'branch_id' => $vacancy->branch_id,
                'branch' => $vacancy->branch?->only(['id', 'name', 'code']),
                'department_id' => $vacancy->department_id,
                'department' => $vacancy->department?->only(['id', 'name']),
                'position_id' => $vacancy->position_id,
                'position' => $vacancy->position?->only(['id', 'name']),
                'structure_id' => $vacancy->structure_id,
                'structure' => $vacancy->structure?->only(['id', 'name']),
                'title' => $vacancy->title,
                'employment_type' => $vacancy->employment_type,
                'requirements' => $vacancy->requirements,
                'schedule' => $vacancy->schedule,
                'salary' => $vacancy->salary,
                'description' => $vacancy->description,
                'status' => $vacancy->status,
                'opened_at' => $vacancy->opened_at?->format('Y-m-d'),
                'closed_at' => $vacancy->closed_at?->format('Y-m-d'),
                'creator' => $vacancy->creator?->only(['id', 'name']),
            ]);

        // Reference data for selects (scoped to visible branches).
        $departmentsQuery = Department::query()->orderBy('name');
        if (!$user->hasRole('Admin')) {
            $departmentsQuery->where('branch_id', $user->branch_id ?? 0);
        }

        return Inertia::render('Vacancies/Index', [
            'vacancies' => $vacancies,
            'branches' => $branches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ])->values(),
            'departments' => $departmentsQuery->get(['id', 'branch_id', 'name']),
            'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
            'structures' => Structure::query()->orderBy('name')->get(['id', 'name']),
            'employmentTypes' => collect(EmploymentType::cases())->map(fn (EmploymentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'filters' => [
                'branch_id' => $branchId,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Vacancy::class);

        $user = $request->user();
        $branchId = $this->resolveBranchId($request, $user);

        $request->merge(['branch_id' => $branchId]);
        $validated = $this->validateVacancy($request, $branchId);

        $validated['created_by'] = $user->id;
        $validated['status'] = Vacancy::STATUS_OPEN;
        $validated['opened_at'] = $validated['opened_at'] ?? Carbon::today()->toDateString();
        $validated['closed_at'] = null;

        $vacancy = Vacancy::create($validated);

        activity()
            ->performedOn($vacancy)
            ->event('created')
            ->log("Вакансия эҷод шуд: {$vacancy->title}");

        return redirect()->route('vacancies.index', $this->indexParams($request, $branchId))
            ->with('success', 'Вакансия бомуваффақият эҷод шуд.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vacancy $vacancy): RedirectResponse
    {
        Gate::authorize('update', $vacancy);

        $user = $request->user();
        $branchId = $user->hasRole('Admin')
            ? ($request->integer('branch_id') ?: $vacancy->branch_id)
            : (int) $vacancy->branch_id;

        $request->merge(['branch_id' => $branchId]);
        $validated = $this->validateVacancy($request, $branchId);

        $status = $request->input('status');
        if (in_array($status, [Vacancy::STATUS_OPEN, Vacancy::STATUS_CLOSED], true)) {
            $validated['status'] = $status;

            if ($status === Vacancy::STATUS_CLOSED && $vacancy->status !== Vacancy::STATUS_CLOSED) {
                $validated['closed_at'] = Carbon::today()->toDateString();
            }

            if ($status === Vacancy::STATUS_OPEN) {
                $validated['closed_at'] = null;
            }
        }

        $vacancy->update($validated);

        activity()
            ->performedOn($vacancy)
            ->event('updated')
            ->log("Вакансия навсозӣ шуд: {$vacancy->title}");

        return redirect()->route('vacancies.index', $this->indexParams($request, $branchId))
            ->with('success', 'Вакансия бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Vacancy $vacancy): RedirectResponse
    {
        Gate::authorize('delete', $vacancy);

        $branchId = $vacancy->branch_id;
        $title = $vacancy->title;

        $vacancy->delete();

        activity()
            ->event('deleted')
            ->log("Вакансия нест карда шуд: {$title}");

        return redirect()->route('vacancies.index', $this->indexParams($request, $branchId))
            ->with('success', 'Вакансия бомуваффақият нест карда шуд.');
    }

    private function resolveBranchId(Request $request, $user): int
    {
        if (!$user->hasRole('Admin')) {
            if ($user->branch_id === null) {
                abort(403);
            }

            if ($request->filled('branch_id') && (int) $request->input('branch_id') !== (int) $user->branch_id) {
                abort(403);
            }

            return (int) $user->branch_id;
        }

        return (int) $request->integer('branch_id');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVacancy(Request $request, int $branchId): array
    {
        $employmentTypes = array_map(fn ($type) => $type->value, EmploymentType::cases());

        return $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'structure_id' => ['nullable', 'integer', Rule::exists('structures', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in($employmentTypes)],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'opened_at' => ['nullable', 'date'],
        ], [], [
            'branch_id' => 'филиал',
            'department_id' => 'шуъба',
            'position_id' => 'вазифа',
            'structure_id' => 'сохтор',
            'title' => 'ном',
            'employment_type' => 'намуди шуғл',
            'requirements' => 'талабот',
            'schedule' => 'ҷадвал',
            'salary' => 'маош',
            'description' => 'тавсиф',
            'opened_at' => 'санаи кушодашавӣ',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexParams(Request $request, int $branchId): array
    {
        $params = [];

        if ($request->user()->hasRole('Admin')) {
            $params['branch_id'] = $branchId;
        }

        return $params;
    }
}
