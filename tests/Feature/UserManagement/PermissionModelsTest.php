<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserManagementSeeder;

test('custom role and permission metadata preserves Spatie name based APIs', function () {
    /** @var Permission $permission */
    $permission = Permission::findOrCreate('users.view');
    $permission->update([
        'display_name' => 'View users',
        'short_note' => 'View the user list.',
    ]);

    /** @var Role $role */
    $role = Role::findOrCreate('administrator');
    $role->update([
        'display_name' => 'Administrator',
        'short_note' => 'Full administration access.',
    ]);
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole('administrator');

    expect($user->hasRole('administrator'))->toBeTrue()
        ->and($user->can('users.view'))->toBeTrue()
        ->and($role->display_name)->toBe('Administrator')
        ->and($permission->display_name)->toBe('View users');
});

test('package created roles and permissions receive readable display fallbacks', function () {
    /** @var Role $role */
    $role = Role::findOrCreate('support-manager');
    /** @var Permission $permission */
    $permission = Permission::findOrCreate('users.export');

    expect($role->display_name)->toBe('Support Manager')
        ->and($permission->display_name)->toBe('Users Export');
});

test('user full name is exposed to the application and passkeys', function () {
    $user = User::factory()->make([
        'first_name' => 'Admin',
        'last_name' => 'User',
    ]);

    expect($user->full_name)->toBe('Admin User')
        ->and($user->getPasskeyDisplayName())->toBe('Admin User')
        ->and($user->toArray()['full_name'])->toBe('Admin User');
});

test('user management seeder stores canonical names and display metadata', function () {
    $this->seed(UserManagementSeeder::class);

    /** @var Role $role */
    $role = Role::findByName('admin');
    /** @var Permission $permission */
    $permission = Permission::findByName('users.view');

    expect($role->display_name)->toBe('Administrator')
        ->and($role->hasPermissionTo('users.view'))->toBeTrue()
        ->and($permission->display_name)->toBe('View users')
        ->and($permission->short_note)->not->toBeEmpty();
});
