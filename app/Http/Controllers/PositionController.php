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

    /**
     * Display a listing of the resource.
     */
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
     * List the employees assigned to a position. Lazy-loaded by the positions
     * grid when a card is opened, so the index stays light (it ships counts,
     * not the full staff lists). Scoped to the employees the user may view —
     * the same filter behind the card's employees_count.
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request): RedirectResponse
    {
        $position = $this->positions->create($request->validated());

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$position->name}' бомуваффақият эҷод шуд.");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, int $id): RedirectResponse
    {
        $position = Position::findOrFail($id);

        $this->positions->update($position, $request->validated());

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$position->name}' бомуваффақият навсозӣ шуд.");
    }

    /**
     * Remove the specified resource from storage.
     */
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
