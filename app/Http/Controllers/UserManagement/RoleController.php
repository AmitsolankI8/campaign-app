<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\IndexRoleRequest;
use App\Http\Requests\UserManagement\StoreRoleRequest;
use App\Http\Requests\UserManagement\UpdateRoleRequest;
use App\Http\Resources\UserManagement\ManagedRoleResource;
use App\Http\Resources\UserManagement\RoleRowResource;
use App\Models\Role;
use App\Support\DataTable;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(IndexRoleRequest $request): Response
    {
        Gate::authorize('roles.view');

        return Inertia::render('user-management/roles/Index', [
            'roles' => fn () => DataTable::make(
                Role::query()
                    ->select(['id', 'public_id', 'name', 'display_name', 'short_note', 'created_at'])
                    ->withCount('users'),
                $request,
                RoleRowResource::class,
            ),
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

        $role->loadMissing('permissions');

        return Inertia::render('user-management/roles/Edit', [
            'role' => $role->toResource(ManagedRoleResource::class)->resolve(),
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
