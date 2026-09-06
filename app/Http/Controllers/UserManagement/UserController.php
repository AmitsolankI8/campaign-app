<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('users.view');

        return Inertia::render('user-management/users/Index', [
            'users' => User::query()
                ->with('roles:id,name,display_name')
                ->latest()
                ->get(['id', 'first_name', 'last_name', 'email', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toDateString(),
                    'roles' => $user->roles->pluck('display_name')->values(),
                    'can_delete' => ! $user->hasRole('admin') && ! $user->is(auth()->user()),
                    'can_edit' => ! $user->hasRole('admin') || $user->is(auth()->user()),
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('users.create');

        return Inertia::render('user-management/users/Create', [
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['first_name', 'last_name', 'email', 'password']));
        $user->syncRoles($request->validated('roles', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('user-management.users.index');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('users.edit');

        abort_if($user->hasRole('admin') && ! $user->is(auth()->user()), 403, __('Only the administrator can edit their own account.'));

        return Inertia::render('user-management/users/Edit', [
            'managedUser' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name'),
                'roles_locked' => $user->hasRole('admin'),
                'can_edit' => ! $user->hasRole('admin') || $user->is(auth()->user()),
            ],
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['first_name', 'last_name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        if (! $user->hasRole('admin')) {
            $user->syncRoles($request->validated('roles', []));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('user-management.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('users.delete');

        abort_if($user->hasRole('admin'), 422, __('The administrator user cannot be deleted.'));

        abort_if($user->is(auth()->user()), 422, __('You cannot delete your own account here.'));

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('user-management.users.index');
    }

    /**
     * @return array<int, array{name: string, display_name: string}>
     */
    private function roles(): array
    {
        return Role::query()
            ->orderBy('display_name')
            ->get(['name', 'display_name'])
            ->map(fn (Role $role) => [
                'name' => $role->name,
                'display_name' => $role->display_name,
            ])
            ->all();
    }
}
