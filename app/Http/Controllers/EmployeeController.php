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
use App\Models\User;
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
    /**
     * Связи, жадно загружаемые для экранов списка и архива сотрудников, со
     * выборкой только тех столбцов, что читают таблица, диалоги просмотра и
     * редактирования — чтобы сериализация страницы не гидрировала четыре полные
     * связанные модели, а добавленные аксессоры
     * nationality/education/specialty/birth_place не порождали четыре ленивых
     * запроса на строку.
     *
     * @var list<string>
     */
    private const LIST_RELATIONS = [
        'branch:id,name', 'department:id,name', 'position:id,name', 'manager:id,full_name',
        'nationalityRef:id,name', 'educationRef:id,name', 'specialtyRef:id,name', 'birthPlaceRef:id,name',
    ];

    public function __construct(private readonly EmployeeService $employees) {}

    /**
     * Отображает список активных сотрудников.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $base = Employee::with(self::LIST_RELATIONS)
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
     * Ищет сотрудников для выбора "непосредственного руководителя" (на сервере,
     * с лимитом), чтобы форма не передавала весь штат. Для не-админов ограничено
     * филиалом пользователя — теми сотрудниками, кем он может управлять.
     */
    public function managers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();
        $term = trim((string) $request->input('search', ''));

        $managers = Employee::query()
            ->active()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($term !== '', fn ($q) => $q->where('full_name', 'like', "%{$term}%"))
            ->orderBy('full_name')
            ->limit(20)
            ->get(['id', 'full_name']);

        return response()->json($managers);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employees->create($request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият илова шуд.');
    }

    public function update(UpdateEmployeeRequest $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        $this->employees->update($employee, $request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият навсозӣ шуд.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        Gate::authorize('delete', $employee);

        $this->employees->delete($employee);

        return redirect()->route('employees.index')
            ->with('success', 'Корманд бомуваффақият нест карда шуд.');
    }

    /**
     * Восстанавливает уволенного (архивного) сотрудника в активный состав.
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
     * Отображает список уволенных/архивных сотрудников.
     */
    public function archive(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $base = Employee::with(self::LIST_RELATIONS)
            ->dismissed()
            ->viewableBy($user)
            ->latest('dismissal_date')
            ->latest('id');

        $employees = QueryBuilder::for($base)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('dismissal_reason'),
            ])
            ->paginate(10)
            ->withQueryString();

        $branches = $user->branch_id !== null || $user->isAdmin()
            ? Branch::orderBy('name')->get()
            : collect();

        // Опции фильтра «сабаби озодшавӣ» — различные уже введённые причины среди
        // видимых пользователю уволенных сотрудников (свободный текст, без enum).
        $dismissalReasons = Employee::dismissed()
            ->viewableBy($user)
            ->whereNotNull('dismissal_reason')
            ->distinct()
            ->orderBy('dismissal_reason')
            ->pluck('dismissal_reason');

        return Inertia::render('Employees/Archive', [
            'employees' => $employees,
            'branches' => $branches,
            'dismissalReasons' => $dismissalReasons,
            'filters' => $request->input('filter', []),
        ]);
    }

    /**
     * Переводит (ротация) сотрудника в новый филиал, на новую должность или в подразделение.
     */
    public function rotate(RotateEmployeeRequest $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        $this->employees->rotate($employee, $request->validated());

        return redirect()->back()
            ->with('success', 'Ротатсияи корманд бомуваффақият анҷом дода шуд.');
    }

    /**
     * Отображает хронологию/список всех ротаций.
     */
    public function rotationsIndex(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $user = $request->user();

        $rotations = Rotation::with(['employee', 'oldBranch', 'newBranch', 'oldPosition', 'newPosition', 'oldDepartment', 'newDepartment'])
            ->when(! $user->isAdmin(), function ($q) use ($user) {
                // Пользователи филиала видят только ротации в свой филиал или из него.
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
     * Полностью стирает историю ротаций. Только для админов и необратимо —
     * аналогично очистке журнала действий.
     */
    public function clearRotations(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        // Bulk-delete минует события модели, поэтому фиксируем это необратимое
        // действие в журнале вручную (с числом стёртых записей).
        $count = Rotation::query()->count();
        Rotation::query()->delete();

        activity()
            ->event('deleted')
            ->log("Таърихи ҷобаҷогузорӣ пурра тоза карда шуд ({$count} сабт)");

        return back()->with('success', 'Таърихи ҷобаҷогузорӣ пурра тоза карда шуд.');
    }

    /**
     * Справочные данные для экрана сотрудников.
     *
     * Для фильтров панели инструментов при первой отрисовке нужны только
     * филиалы/подразделения/типы, поэтому они загружаются сразу. Всё остальное
     * используется только диалогами создания/редактирования и ротации, поэтому
     * откладывается (группа Inertia "form"): сначала рендерится список, а данные
     * формы подгружаются после, и они пропускаются при частичных перезагрузках
     * по фильтру/пагинации (`only`). Пользователь без филиала (и не админ) не
     * может управлять сотрудниками → пустые списки.
     *
     * @return array<string, mixed>
     */
    private function referenceData(User $user): array
    {
        $isAdmin = $user->isAdmin();
        $canManage = $isAdmin || $user->branch_id !== null;
        $branchId = $user->branch_id;

        return [
            // Сразу — нужны фильтрам панели инструментов.
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

            // Отложено (группа "form") — используется только диалогами, поэтому
            // не утяжеляет начальную загрузку списка и его частичные перезагрузки.
            'categories' => Inertia::defer(fn () => Category::options(), 'form'),
            'positions' => Inertia::defer(fn () => $canManage ? Position::orderBy('name')->get() : collect(), 'form'),
            // руководители подгружаются по запросу через поисковый эндпоинт
            // (employees.managers) — никогда не передаются полным списком.
            'nationalities' => Inertia::defer(fn () => Nationality::orderBy('name')->pluck('name'), 'form'),
            'educations' => Inertia::defer(fn () => Education::orderBy('name')->pluck('name'), 'form'),
            'specialties' => Inertia::defer(fn () => Specialty::orderBy('name')->pluck('name'), 'form'),
            'birthPlaces' => Inertia::defer(fn () => BirthPlace::orderBy('name')->pluck('name'), 'form'),
        ];
    }
}
