<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('users.view');

        return Inertia::render('user-management/users/Index', [
            'users' => User::query()
                ->with('roles:id,name')
                ->latest()
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toDateString(),
                    'roles' => $user->roles->pluck('name')->values(),
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('users.create');

        return Inertia::render('user-management/users/Create', [
            'roles' => $this->roles(),
            'permissionGroups' => PermissionRegistry::groups(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));
        $user->syncRoles($request->validated('roles', []));
        $user->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('user-management.users.index');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('users.edit');

        return Inertia::render('user-management/users/Edit', [
            'managedUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name'),
                'permissions' => $user->permissions()->pluck('name'),
            ],
            'roles' => $this->roles(),
            'permissionGroups' => PermissionRegistry::groups(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();
        $user->syncRoles($request->validated('roles', []));
        $user->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('user-management.users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('users.delete');

        abort_if($user->is(auth()->user()), 422, __('You cannot delete your own account here.'));

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('user-management.users.index');
    }

    /**
     * @return array<int, array{name: string}>
     */
    private function roles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (Role $role) => ['name' => $role->name])
            ->all();
    }
}
