<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with(['branch', 'roles']);

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->latest('id')->paginate(10)->withQueryString();

        $branches = Branch::orderBy('name')->get();
        $roles = Role::whereIn('name', [User::ROLE_ADMIN, User::ROLE_USER])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'name' => $role->name,
                'label' => match ($role->name) {
                    User::ROLE_ADMIN => 'Админ',
                    User::ROLE_USER => 'Корбар',
                    default => $role->name,
                },
            ])
            ->values();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'branches' => $branches,
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $branchId = $validated['role'] === User::ROLE_ADMIN ? null : $validated['branch_id'];

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch_id' => $branchId,
        ]);
        $user->disableLogging()->save();

        $user->assignRole($validated['role']);

        activity()
            ->performedOn($user)
            ->event('created')
            ->log("Корбар эҷод шуд: {$user->name} ({$user->email}), нақш: {$validated['role']}");

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият эҷод шуд.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['role'] === User::ROLE_ADMIN ? null : $validated['branch_id'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->disableLogging()->update($updateData);

        // Sync roles using Spatie
        $user->syncRoles([$validated['role']]);

        activity()
            ->performedOn($user)
            ->event('updated')
            ->log("Корбар навсозӣ шуд: {$user->name} ({$user->email}), нақш: {$validated['role']}");

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият навсозӣ шуд.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'Шумо наметавонед аккаунти худро нест кунед.');
        }

        $name = $user->name;
        $email = $user->email;

        activity()
            ->performedOn($user)
            ->event('deleted')
            ->log("Корбар нест карда шуд: {$name} ({$email})");

        $user->disableLogging()->delete();

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият нест карда шуд.');
    }
}
