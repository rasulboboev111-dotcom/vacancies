<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branches) {}

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->branches->create($request->validated());

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият эҷод шуд.');
    }

    public function update(UpdateBranchRequest $request, int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        $this->branches->update($branch, $request->validated());

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият навсозӣ шуд.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        Gate::authorize('delete', $branch);

        $this->branches->delete($branch);

        return redirect()->route('structure.index')
            ->with('success', 'Филиал бомуваффақият нест карда шуд.');
    }
}
