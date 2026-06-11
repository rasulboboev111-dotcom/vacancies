<?php

namespace App\Http\Controllers;

use App\Data\VacancyData;
use App\Enums\Education;
use App\Enums\Experience;
use App\Enums\OpeningReason;
use App\Enums\Probation;
use App\Enums\ScheduleType;
use App\Enums\VacancyEmploymentType;
use App\Enums\VacancyPriority;
use App\Enums\VacancyStatus;
use App\Enums\WorkFormat;
use App\Http\Requests\Vacancy\StoreVacancyRequest;
use App\Http\Requests\Vacancy\UpdateVacancyRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Vacancy;
use App\Services\VacancyService;
use Illuminate\Contracts\View\View;
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

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vacancy::class);

        $user = $request->user();
        $branches = collect();

        if ($user->isAdmin() || $user->branch_id !== null) {
            $branches = Branch::query()->orderBy('name')->get(['id', 'name', 'code']);
        }

        $base = Vacancy::query()
            ->with(['branch:id,name,code', 'department:id,name', 'position:id,name', 'creator:id,name', 'languages'])
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
        if (! $user->isAdmin()) {
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
            'formOptions' => [
                'educations' => Education::options(),
                'experiences' => Experience::options(),
                'employmentTypes' => VacancyEmploymentType::options(),
                'scheduleTypes' => ScheduleType::options(),
                'workFormats' => WorkFormat::options(),
                'probations' => Probation::options(),
                'openingReasons' => OpeningReason::options(),
                'priorities' => VacancyPriority::options(),
                'knownLanguages' => config('hiring.known_languages'),
            ],
            'filters' => $request->input('filter', []),
        ]);
    }

    /**
     * Печатная «Заявка на подбор персонала» — точное воспроизведение официальной
     * docx-формы (Приложение № 1 к СОП), заполненное данными вакансии.
     */
    public function print(int $id): View
    {
        $vacancy = Vacancy::with(['branch', 'department', 'position', 'creator', 'languages'])->findOrFail($id);

        Gate::authorize('view', $vacancy);

        return view('vacancies.print', ['vacancy' => $vacancy]);
    }

    public function store(StoreVacancyRequest $request): RedirectResponse
    {
        $this->vacancies->create($request->validated(), $request->user());

        return redirect()->route('vacancies.index', $this->indexParams($request))
            ->with('success', 'Вакансия бомуваффақият эҷод шуд.');
    }

    public function update(UpdateVacancyRequest $request, int $id): RedirectResponse
    {
        $vacancy = Vacancy::findOrFail($id);

        $this->vacancies->update($vacancy, $request->validated(), $request->input('status'));

        return redirect()->route('vacancies.index', $this->indexParams($request))
            ->with('success', 'Вакансия бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $vacancy = Vacancy::findOrFail($id);

        Gate::authorize('delete', $vacancy);

        $this->vacancies->delete($vacancy);

        return redirect()->route('vacancies.index', $this->indexParams($request))
            ->with('success', 'Вакансия бомуваффақият нест карда шуд.');
    }

    /**
     * Сохраняет фильтр списка по филиалу/статусу, который пользователь
     * действительно выбрал, А НЕ филиал изменяемой вакансии. Отсутствие фильтра
     * означает «все филиалы».
     *
     * @return array<string, mixed>
     */
    private function indexParams(Request $request): array
    {
        $filter = [];

        if ($request->user()->isAdmin()) {
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
