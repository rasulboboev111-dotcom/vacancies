<?php

namespace App\Http\Controllers;

use App\Exceptions\PositionInUseException;
use App\Http\Requests\Position\StorePositionRequest;
use App\Http\Requests\Position\UpdatePositionRequest;
use App\Models\Employee;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function __construct(private readonly PositionService $positions) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Position::class);

        $user = $request->user();

        $query = Position::withCount(['employees' => fn ($q) => $q->viewableBy($user)]);

        if (! $user->isAdmin() && $user->branch_id === null) {
            $query->whereRaw('1=0');
        }

        $positions = $query->orderBy('name')->get();

        return Inertia::render('Positions/Index', [
            'positions' => $positions,
        ]);
    }

    /**
     * Список сотрудников, назначенных на должность. Подгружается лениво сеткой
     * должностей при открытии карточки, чтобы индекс оставался лёгким (он
     * передаёт счётчики, а не полные списки штата). Ограничено сотрудниками,
     * доступными пользователю — тот же фильтр, что стоит за employees_count
     * карточки.
     */
    public function employees(Request $request, int $id): JsonResponse
    {
        Gate::authorize('viewAny', Position::class);

        $position = Position::findOrFail($id);

        $employees = Employee::query()
            ->where('position_id', $position->id)
            ->viewableBy($request->user())
            ->without(['nationalityRef', 'educationRef', 'specialtyRef', 'birthPlaceRef'])
            ->with(['department:id,name', 'branch:id,name'])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'department_id', 'branch_id'])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'department' => $employee->department?->getAttribute('name'),
                'branch' => $employee->branch?->getAttribute('name'),
            ]);

        return response()->json([
            'position' => [
                'id' => $position->id,
                'name' => $position->name,
            ],
            'employees' => $employees,
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $position = $this->positions->create($request->validated());

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$position->name}' бомуваффақият эҷод шуд.");
    }

    public function update(UpdatePositionRequest $request, int $id): RedirectResponse
    {
        $position = Position::findOrFail($id);

        $this->positions->update($position, $request->validated());

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$position->name}' бомуваффақият навсозӣ шуд.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $position = Position::findOrFail($id);

        Gate::authorize('delete', $position);

        $name = $position->name;

        try {
            $this->positions->delete($position);
        } catch (PositionInUseException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$name}' бомуваффақият нест карда шуд.");
    }
}
