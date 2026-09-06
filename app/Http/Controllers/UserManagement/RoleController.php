<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreRoleRequest;
use App\Http\Requests\UserManagement\UpdateRoleRequest;
use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('roles.view');

        return Inertia::render('user-management/roles/Index', [
            'roles' => Role::query()
                ->withCount('users')
                ->orderBy('display_name')
                ->get(['id', 'name', 'display_name', 'short_note'])
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'short_note' => $role->short_note,
                    'users_count' => $role->users_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('roles.create');

        return Inertia::render('user-management/roles/Create', [
            'permissionGroups' => PermissionRegistry::groups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create($request->safe()->only(['name', 'display_name', 'short_note']));
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('user-management.roles.index');
    }

    public function edit(Role $role): Response
    {
        Gate::authorize('roles.edit');

        return Inertia::render('user-management/roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'short_note' => $role->short_note,
                'permissions' => $role->permissions()->pluck('name'),
            ],
            'permissionGroups' => PermissionRegistry::groups(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update($request->safe()->only(['name', 'display_name', 'short_note']));
        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('user-management.roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('roles.delete');

        abort_if($role->users()->exists(), 422, __('Remove this role from users before deleting it.'));

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('user-management.roles.index');
    }
}
