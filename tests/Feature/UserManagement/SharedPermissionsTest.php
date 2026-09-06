<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('shared permissions include direct and inherited grants and refresh after revocation', function () {
    Permission::findOrCreate('users.view');
    Permission::findOrCreate('roles.create');
    $role = Role::findOrCreate('viewer');
    $role->givePermissionTo('users.view');
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->givePermissionTo('roles.create');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', ['users.view', 'roles.create']));

    $user->revokePermissionTo('roles.create');
    $role->revokePermissionTo('users.view');
    $user->unsetRelation('roles')->unsetRelation('permissions');

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', []));
});

test('guests and users without grants receive no shared permissions', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', []));

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.permissions', []));
});

test('view permission does not authorize management actions', function (string $resource) {
    Permission::findOrCreate("{$resource}.view");
    $user = User::factory()->create();
    $user->givePermissionTo("{$resource}.view");

    $this->actingAs($user)->get(route("user-management.{$resource}.index"))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', ["{$resource}.view"]));
    $this->get(route("user-management.{$resource}.create"))->assertForbidden();
    $this->post(route("user-management.{$resource}.store"), [])->assertForbidden();
})->with(['users', 'roles']);
