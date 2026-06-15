<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = QueryBuilder::for(User::with(['branch', 'roles']))
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

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
            'filters' => $request->input('filter', []),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият эҷод шуд.');
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $this->users->update($user, $request->validated());

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият навсозӣ шуд.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        Gate::authorize('delete', $user);

        if ($request->user()->id === $user->id) {
            return redirect()->back()->with('error', 'Шумо наметавонед аккаунти худро нест кунед.');
        }

        $this->users->delete($user);

        return redirect()->route('users.index')
            ->with('success', 'Корбар бомуваффақият нест карда шуд.');
    }
}
