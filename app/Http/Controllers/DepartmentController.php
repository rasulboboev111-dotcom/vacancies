<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departments) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->departments->create($request->validated());

        return redirect()->route('structure.index')
            ->with('success', 'Шуъба бомуваффақият эҷод шуд.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departments->update($department, $request->validated());

        return redirect()->route('structure.index')
            ->with('success', 'Шуъба бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('delete', $department);

        if (! $this->departments->delete($department)) {
            // Distinguish a visible blocker (active children) from a hidden one
            // (only soft-deleted children) so the message is actionable.
            $message = $department->children()->exists()
                ? 'Шуъбаро бо зершуъбаҳояш нест кардан мумкин нест.'
                : 'Ин шуъба зершуъбаҳои бойгонишуда (дар сабад) дорад. Аввал онҳоро аз сабад тамоман нест кунед.';

            return redirect()->route('structure.index')->withErrors(['message' => $message]);
        }

        return redirect()->route('structure.index')
            ->with('success', 'Шуъба бомуваффақият нест карда шуд.');
    }
}
