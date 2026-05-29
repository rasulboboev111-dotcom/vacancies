<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
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
            ->log("Филиал эҷод шуд: {$branch->name} ({$branch->code})");

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият эҷод шуд.');
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
            ->log("Филиал навсозӣ шуд: {$branch->name} ({$branch->code})");

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият навсозӣ шуд.');
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
            ->log("Филиал нест карда шуд: {$name} ({$code})");

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият нест карда шуд.');
    }
}
