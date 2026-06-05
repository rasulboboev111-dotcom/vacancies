<?php

namespace App\Http\Controllers;

use App\Data\VacancyData;
use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Http\Requests\Vacancy\StoreVacancyRequest;
use App\Http\Requests\Vacancy\UpdateVacancyRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Vacancy;
use App\Services\VacancyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VacancyController extends Controller
{
    public function __construct(private readonly VacancyService $vacancies) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vacancy::class);

        $user = $request->user();
        $branches = collect();

        if ($user->hasRole('Admin') || $user->branch_id !== null) {
            $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        $base = Vacancy::query()
            ->with(['branch:id,name,code', 'department:id,name', 'position:id,name', 'creator:id,name'])
            ->viewableBy($user)
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->latest('opened_at')
            ->latest('id');

        $vacancies = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('status'),
            ])
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Vacancy $vacancy) => VacancyData::from($vacancy));

        $departmentsQuery = Department::query()->orderBy('name');
        if (! $user->hasRole('Admin')) {
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
            'employmentTypes' => collect(EmploymentType::cases())->map(fn (EmploymentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'filters' => $request->input('filter', []),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVacancyRequest $request): RedirectResponse
    {
        $this->vacancies->create($request->validated(), $request->user());

        return redirect()->route('vacancies.index', $this->indexParams($request))
            ->with('success', 'Вакансия бомуваффақият эҷод шуд.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVacancyRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $this->vacancies->update($vacancy, $request->validated(), $request->input('status'));

        return redirect()->route('vacancies.index', $this->indexParams($request))
            ->with('success', 'Вакансия бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Vacancy $vacancy): RedirectResponse
    {
        Gate::authorize('delete', $vacancy);

        $this->vacancies->delete($vacancy);

        return redirect()->route('vacancies.index', $this->indexParams($request))
            ->with('success', 'Вакансия бомуваффақият нест карда шуд.');
    }

    /**
     * Preserve the list's branch/status filter that the user actually selected,
     * NOT the branch of the vacancy being changed. Absent filter means
     * "all branches".
     *
     * @return array<string, mixed>
     */
    private function indexParams(Request $request): array
    {
        $filter = [];

        if ($request->user()->hasRole('Admin')) {
            $filterBranchId = $request->integer('filter_branch_id') ?: null;
            if ($filterBranchId) {
                $filter['branch_id'] = $filterBranchId;
            }
        }

        $filterStatus = $request->input('filter_status');
        if (in_array($filterStatus, VacancyStatus::values(), true)) {
            $filter['status'] = $filterStatus;
        }

        return $filter ? ['filter' => $filter] : [];
    }
}
