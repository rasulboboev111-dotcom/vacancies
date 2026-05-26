<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Branch::class);

        $user = $request->user();
        $query = Branch::withCount('employees');

        if (!$user->hasRole('Admin')) {
            if ($user->branch_id !== null) {
                $query->where('id', $user->branch_id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        $branches = $query->orderBy('name')->get();

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Branch::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches,code',
            'address' => 'nullable|string|max:255',
        ]);

        $branch = Branch::create($validated);

        activity()
            ->performedOn($branch)
            ->event('created')
            ->log("Создан филиал: {$branch->name} ({$branch->code})");

        return redirect()->route('branches.index')
            ->with('success', 'Филиал успешно создан.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('update', $branch);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => "required|string|max:10|unique:branches,code,{$branch->id}",
            'address' => 'nullable|string|max:255',
        ]);

        $branch->update($validated);

        activity()
            ->performedOn($branch)
            ->event('updated')
            ->log("Обновлен филиал: {$branch->name} ({$branch->code})");

        return redirect()->route('branches.index')
            ->with('success', 'Филиал успешно обновлен.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        Gate::authorize('delete', $branch);

        $name = $branch->name;
        $code = $branch->code;

        $branch->delete();

        activity()
            ->event('deleted')
            ->log("Удален филиал: {$name} ({$code})");

        return redirect()->route('branches.index')
            ->with('success', 'Филиал успешно удален.');
    }
}
