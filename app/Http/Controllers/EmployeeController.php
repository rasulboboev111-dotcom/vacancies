<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\RotateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Rotation;
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
     * Отображает список активных сотрудников.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        return Inertia::render('Employees/Index', $this->employees->panelData($request->user(), $request));
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

        return back()->with('success', 'Корманд бомуваффақият илова шуд.');
    }

    public function update(UpdateEmployeeRequest $request, int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        $this->employees->update($employee, $request->validated());

        return back()->with('success', 'Корманд бомуваффақият навсозӣ шуд.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $employee = Employee::findOrFail($id);

        Gate::authorize('delete', $employee);

        $this->employees->delete($employee);

        return back()->with('success', 'Корманд бомуваффақият нест карда шуд.');
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

        $base = Employee::with(EmployeeService::LIST_RELATIONS)
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
}
