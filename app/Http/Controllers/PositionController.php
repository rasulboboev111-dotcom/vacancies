<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Employee;
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
    public function index(): Response
    {
        Gate::authorize('viewAny', Position::class);

        $positions = Position::withCount('employees')
            ->orderBy('name')
            ->get();

        return Inertia::render('Positions/Index', [
            'positions' => $positions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Position::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
        ], [
            'name.required' => 'Название должности обязательно для заполнения.',
            'name.unique' => 'Такая должность уже существует в базе данных.',
            'name.max' => 'Название должности слишком длинное.',
        ]);

        $position = Position::create($validated);

        activity()
            ->performedOn($position)
            ->event('created')
            ->log("Создана новая должность: {$position->name}");

        return redirect()->route('positions.index')
            ->with('success', "Должность '{$position->name}' успешно создана.");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position): RedirectResponse
    {
        Gate::authorize('update', $position);

        $validated = $request->validate([
            'name' => "required|string|max:255|unique:positions,name,{$position->id}",
        ], [
            'name.required' => 'Название должности обязательно для заполнения.',
            'name.unique' => 'Такая должность уже существует в базе данных.',
            'name.max' => 'Название должности слишком длинное.',
        ]);

        $oldName = $position->name;
        $position->update($validated);

        activity()
            ->performedOn($position)
            ->event('updated')
            ->log("Обновлено название должности: с '{$oldName}' на '{$position->name}'");

        return redirect()->route('positions.index')
            ->with('success', "Должность '{$position->name}' успешно обновлена.");
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
            return redirect()->back()->with('error', "Невозможно удалить должность '{$position->name}', так как она назначена сотрудникам ({$employeeCount} чел.). Сначала переведите их на другую должность.");
        }

        $name = $position->name;
        $position->delete();

        activity()
            ->event('deleted')
            ->log("Удалена должность: {$name}");

        return redirect()->route('positions.index')
            ->with('success', "Должность '{$name}' успешно удалена.");
    }
}
