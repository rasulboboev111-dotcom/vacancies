<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
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
            abort(403, 'Недостаточно прав.');
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
        $roles = Role::orderBy('name')->get();

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
            abort(403, 'Недостаточно прав.');
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
                        $fail('Пользователь с таким email уже существует.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
        ], [
            'email.required' => 'Email обязателен для заполнения.',
            'email.email' => 'Неверный формат email.',
            'password.required' => 'Пароль обязателен для заполнения.',
            'password.confirmed' => 'Пароли не совпадают.',
            'role.required' => 'Роль обязательна для заполнения.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'branch_id' => $validated['branch_id'],
        ]);

        $user->assignRole($validated['role']);

        activity()
            ->performedOn($user)
            ->event('created')
            ->log("Создан пользователь: {$user->name} ({$user->email}), роль: {$validated['role']}");

        return redirect()->route('users.index')
            ->with('success', 'Пользователь успешно создан.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (!$currentUser->hasRole('Admin')) {
            abort(403, 'Недостаточно прав.');
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
                        $fail('Пользователь с таким email уже существует.');
                    }
                },
            ],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
        ], [
            'email.required' => 'Email обязателен для заполнения.',
            'email.email' => 'Неверный формат email.',
            'password.confirmed' => 'Пароли не совпадают.',
            'role.required' => 'Роль обязательна для заполнения.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
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
            ->log("Обновлен пользователь: {$user->name} ({$user->email}), роль: {$validated['role']}");

        return redirect()->route('users.index')
            ->with('success', 'Пользователь успешно обновлен.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (!$currentUser->hasRole('Admin')) {
            abort(403, 'Недостаточно прав.');
        }

        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'Вы не можете удалить собственный аккаунт.');
        }

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        activity()
            ->event('deleted')
            ->log("Удален пользователь: {$name} ({$email})");

        return redirect()->route('users.index')
            ->with('success', 'Пользователь успешно удален.');
    }
}
