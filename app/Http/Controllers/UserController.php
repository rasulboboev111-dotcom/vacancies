<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (!$user->hasRole('Admin')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        $query = User::with(['branch', 'roles']);

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

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
    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        if (!$currentUser->hasRole('Admin')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        $request->merge([
            'email' => strtolower(trim($request->email))
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = User::whereRaw('LOWER(TRIM(email)) = ?', [$value])->exists();
                    if ($exists) {
                        $fail('Корбар бо чунин почтаи электронӣ аллакай мавҷуд аст.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'branch_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_USER),
                'nullable',
                'exists:branches,id',
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ], [
            'email.required' => 'Почтаи электронӣ ҳатмист.',
            'email.email' => 'Формати почтаи электронӣ нодуруст аст.',
            'password.required' => 'Парол ҳатмист.',
            'password.confirmed' => 'Паролҳо мувофиқат намекунанд.',
            'role.required' => 'Нақш ҳатмист.',
            'branch_id.required' => 'Барои нақши «Корбар» бояд филиал зикр карда шавад.',
        ]);

        $branchId = $validated['role'] === User::ROLE_ADMIN ? null : $validated['branch_id'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch_id' => $branchId,
        ]);

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
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (!$currentUser->hasRole('Admin')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        $request->merge([
            'email' => strtolower(trim($request->email))
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    $exists = User::whereRaw('LOWER(TRIM(email)) = ?', [$value])
                        ->where('id', '!=', $user->id)
                        ->exists();
                    if ($exists) {
                        $fail('Корбар бо чунин почтаи электронӣ аллакай мавҷуд аст.');
                    }
                },
            ],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'branch_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_USER),
                'nullable',
                'exists:branches,id',
            ],
            'role' => ['required', 'string', Rule::in([User::ROLE_ADMIN, User::ROLE_USER])],
        ], [
            'email.required' => 'Почтаи электронӣ ҳатмист.',
            'email.email' => 'Формати почтаи электронӣ нодуруст аст.',
            'password.confirmed' => 'Паролҳо мувофиқат намекунанд.',
            'role.required' => 'Нақш ҳатмист.',
            'branch_id.required' => 'Барои нақши «Корбар» бояд филиал зикр карда шавад.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['role'] === User::ROLE_ADMIN ? null : $validated['branch_id'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

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
        $currentUser = $request->user();
        if (!$currentUser->hasRole('Admin')) {
            abort(403, 'Ҳуқуқ нокифоя аст.');
        }

        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'Шумо наметавонед аккаунти худро нест кунед.');
        }

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        activity()
            ->event('deleted')
            ->log("Корбар нест карда шуд: {$name} ({$email})");

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият нест карда шуд.');
    }
}
