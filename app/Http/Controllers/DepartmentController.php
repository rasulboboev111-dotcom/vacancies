<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Department::class);

        $user = $request->user();

        if (!$user->hasRole('Admin')) {
            if ($request->filled('branch_id') && (int) $request->input('branch_id') !== (int) $user->branch_id) {
                abort(403);
            }

            if ($user->branch_id === null) {
                abort(403);
            }
        }

        $branchId = $user->hasRole('Admin')
            ? $request->integer('branch_id')
            : (int) $user->branch_id;

        $request->merge(['branch_id' => $branchId]);

        $validated = $this->validateDepartment($request, null, $branchId);

        $validated['branch_id'] = (int) $validated['branch_id'];
        $validated['parent_id'] = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        $this->assertParentIsValid($validated['parent_id'], $validated['branch_id']);

        $department = Department::create($validated);

        activity()
            ->performedOn($department)
            ->event('created')
            ->log("Шуъба эҷод шуд: {$department->name}");

        return redirect()->route('structure.index')
            ->with('success', 'Шуъба бомуваффақият эҷод шуд.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        Gate::authorize('update', $department);

        $user = $request->user();
        $branchId = $user->hasRole('Admin')
            ? $request->integer('branch_id')
            : (int) $department->branch_id;

        $request->merge(['branch_id' => $branchId]);

        $validated = $this->validateDepartment($request, $department, $branchId);

        $validated['branch_id'] = (int) $validated['branch_id'];
        $validated['parent_id'] = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        $this->assertParentIsValid($validated['parent_id'], $validated['branch_id'], $department);

        if ($department->wouldCreateCycle($validated['parent_id'] ?? null)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Зершуъбаро ҳамчун шуъбаи болоӣ таъин кардан мумкин нест.',
            ]);
        }

        $department->update($validated);

        activity()
            ->performedOn($department)
            ->event('updated')
            ->log("Шуъба навсозӣ шуд: {$department->name}");

        return redirect()->route('structure.index')
            ->with('success', 'Шуъба бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('delete', $department);

        if ($department->children()->exists()) {
            return redirect()->route('structure.index')
                ->withErrors(['message' => 'Шуъбаро бо зершуъбаҳояш нест кардан мумкин нест.']);
        }

        $name = $department->name;

        $department->delete();

        activity()
            ->event('deleted')
            ->log("Шуъба нест карда шуд: {$name}");

        return redirect()->route('structure.index')
            ->with('success', 'Шуъба бомуваффақият нест карда шуд.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDepartment(Request $request, ?Department $department, int $branchId): array
    {
        $parentId = $request->input('parent_id');
        $parentId = $parentId === null || $parentId === '' ? null : (int) $parentId;

        return $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('branch_id', $branchId)
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(fn ($query) => $query
                        ->where('branch_id', $branchId)
                        ->whereNull('deleted_at')
                        ->when(
                            $parentId,
                            fn ($query) => $query->where('parent_id', $parentId),
                            fn ($query) => $query->whereNull('parent_id'),
                        ))
                    ->ignore($department?->id),
            ],
            'code' => ['nullable', 'string', 'max:20'],
        ], [], [
            'branch_id' => 'филиал',
            'parent_id' => 'шуъбаи болоӣ',
            'name' => 'ном',
            'code' => 'рамз',
        ]);
    }

    private function assertParentIsValid(?int $parentId, int $branchId, ?Department $department = null): void
    {
        if ($parentId === null) {
            return;
        }

        $parentId = (int) $parentId;
        $branchId = (int) $branchId;

        if ($department && $parentId === (int) $department->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Шуъба наметавонад шуъбаи болоии худаш бошад.',
            ]);
        }

        $parent = Department::query()->find($parentId);

        if (!$parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Шуъбаи болоии интихобшуда ёфт нашуд.',
            ]);
        }

        if ((int) $parent->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'parent_id' => 'Шуъбаи болоӣ бояд ба филиали интихобшуда тааллуқ дошта бошад.',
            ]);
        }
    }
}
