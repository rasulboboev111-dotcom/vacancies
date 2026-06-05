<?php

namespace App\Http\Controllers;

use App\Http\Requests\Position\StorePositionRequest;
use App\Http\Requests\Position\UpdatePositionRequest;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Position::class);

        $user = $request->user();

        $query = Position::withCount(['employees' => fn ($q) => $q->viewableBy($user)]);

        if (! $user->hasRole('Admin') && $user->branch_id === null) {
            $query->whereRaw('1=0');
        }

        $positions = $query->orderBy('name')->get();

        return Inertia::render('Positions/Index', [
            'positions' => $positions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request): RedirectResponse
    {
        $position = Position::create($request->validated());

        activity()
            ->performedOn($position)
            ->event('created')
            ->log("Вазифаи нав эҷод шуд: {$position->name}");

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$position->name}' бомуваффақият эҷод шуд.");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $oldName = $position->name;
        $position->update($request->validated());

        activity()
            ->performedOn($position)
            ->event('updated')
            ->log("Номи вазифа навсозӣ шуд: аз '{$oldName}' ба '{$position->name}'");

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$position->name}' бомуваффақият навсозӣ шуд.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): RedirectResponse
    {
        Gate::authorize('delete', $position);

        // Safety check: Prevent deletion if any active or soft-deleted employee is linked to this position
        $employeeCount = Employee::withTrashed()->where('position_id', $position->id)->count();
        if ($employeeCount > 0) {
            return redirect()->back()->with('error', "Вазифаи '{$position->name}'-ро нест кардан мумкин нест, зеро он ба кормандон таъин шудааст ({$employeeCount} нафар). Аввал онҳоро ба вазифаи дигар гузаронед.");
        }

        $name = $position->name;
        $position->delete();

        activity()
            ->performedOn($position)
            ->event('deleted')
            ->log("Вазифа нест карда шуд: {$name}");

        return redirect()->route('positions.index')
            ->with('success', "Вазифаи '{$name}' бомуваффақият нест карда шуд.");
    }
}
