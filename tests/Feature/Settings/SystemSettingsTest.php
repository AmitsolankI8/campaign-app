<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Settings\SystemSettings;
use Database\Seeders\SystemSettingsPermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Permission::factory()->fromRegistry('system-settings.view')->create();
    Permission::factory()->fromRegistry('system-settings.edit')->create();
});

test('settings permissions can be added without replacing existing admin grants', function () {
    Permission::factory()->fromRegistry('users.view')->create();
    $role = Role::factory()->create(['name' => 'admin']);
    $role->givePermissionTo('users.view');
    $this->seed(SystemSettingsPermissionSeeder::class);
    $this->seed(SystemSettingsPermissionSeeder::class);
    expect($role->fresh()->hasAllPermissions(['users.view', 'system-settings.view', 'system-settings.edit']))->toBeTrue();
});

test('guests cannot access system settings', function () {
    $this->get(route('system-settings.edit'))->assertRedirect(route('login'));
    $this->put(route('system-settings.update'), [])->assertRedirect(route('login'));
});

test('missing grants deny access and cannot change settings', function () {
    $original = app(SystemSettings::class)->display_name;
    $this->actingAs(User::factory()->create())
        ->get(route('system-settings.edit'))->assertForbidden();
    $this->put(route('system-settings.update'), ['display_name' => 'Unauthorized'])->assertForbidden();
    expect(app(SystemSettings::class)->refresh()->display_name)->toBe($original);
});

test('viewers can read but cannot update system settings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system-settings.view');
    $this->actingAs($user)->get(route('system-settings.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('system-settings/System')
            ->where('auth.permissions', ['system-settings.view'])
            ->where('settings.display_name', app(SystemSettings::class)->display_name));
    $this->put(route('system-settings.update'), ['display_name' => 'Unauthorized'])->assertForbidden();
});

test('direct and role grants allow persistent system settings updates', function (bool $inherited) {
    $user = User::factory()->create();
    if ($inherited) {
        $role = Role::factory()->create();
        $role->givePermissionTo(['system-settings.view', 'system-settings.edit']);
        $user->assignRole($role);
    } else {
        $user->givePermissionTo(['system-settings.view', 'system-settings.edit']);
    }

    $this->actingAs($user)->put(route('system-settings.update'), [
        'display_name' => 'Campaign Console',
        ...preferencePayload('default_'),
    ])
        ->assertSessionHasNoErrors()->assertRedirect(route('system-settings.edit'));
    $this->assertDatabaseHas('settings', [
        'group' => 'system', 'name' => 'display_name', 'payload' => json_encode('Campaign Console'),
    ]);
    $this->get(route('system-settings.edit'))->assertInertia(fn (Assert $page) => $page
        ->where('settings.display_name', 'Campaign Console')
        ->where('name', 'Campaign Console')
        ->where('auth.permissions', ['system-settings.view', 'system-settings.edit']));

    if ($inherited) {
        $role->revokePermissionTo('system-settings.edit');
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->put(route('system-settings.update'), ['display_name' => 'Revoked'])->assertForbidden();
    }
})->with([false, true]);

test('editing requires view access too', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system-settings.edit');
    $this->actingAs($user)->get(route('system-settings.edit'))->assertForbidden();
    $this->put(route('system-settings.update'), ['display_name' => 'Unauthorized'])->assertForbidden();
});

test('invalid system settings are rejected', function (mixed $name) {
    $user = User::factory()->create();
    $user->givePermissionTo(['system-settings.view', 'system-settings.edit']);
    $original = app(SystemSettings::class)->display_name;
    $this->actingAs($user)->put(route('system-settings.update'), [
        'display_name' => $name,
        ...preferencePayload('default_'),
    ])
        ->assertSessionHasErrors('display_name');
    expect(app(SystemSettings::class)->refresh()->display_name)->toBe($original);
})->with([null, '', '   ', str_repeat('a', 256), 123]);
