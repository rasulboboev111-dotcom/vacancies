<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = new Branch($request->validated());
        $branch->disableLogging()->save();

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
    public function update(UpdateBranchRequest $request, int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        $branch->disableLogging()->update($request->validated());

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
    public function destroy(int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        Gate::authorize('delete', $branch);

        $name = $branch->name;
        $code = $branch->code;

        activity()
            ->performedOn($branch)
            ->event('deleted')
            ->log("Филиал нест карда шуд: {$name} ({$code})");

        $branch->disableLogging()->delete();

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият нест карда шуд.');
    }
}
