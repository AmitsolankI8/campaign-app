<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserManagementSeeder;
use Illuminate\Database\QueryException;

test('custom role and permission metadata preserves Spatie name based APIs', function () {
    /** @var Permission $permission */
    $permission = Permission::create([
        'name' => 'users.view',
        'display_name' => 'View users',
        'short_note' => 'View the user list.',
    ]);

    /** @var Role $role */
    $role = Role::create([
        'name' => 'administrator',
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

test('roles and permissions require display names in the database', function (string $model, array $attributes) {
    expect(fn () => $model::create(['name' => 'example', ...$attributes]))
        ->toThrow(QueryException::class);
})->with([
    'role missing' => [Role::class, []],
    'role null' => [Role::class, ['display_name' => null]],
    'permission missing' => [Permission::class, []],
    'permission null' => [Permission::class, ['display_name' => null]],
]);

test('creating roles and permissions preserves explicit display names', function () {
    $role = Role::create([
        'name' => 'support-manager',
        'display_name' => 'Support team lead',
    ]);
    $permission = Permission::create([
        'name' => 'users.export',
        'display_name' => 'Export user records',
    ]);

    expect($role->fresh()->display_name)->toBe('Support team lead')
        ->and($permission->fresh()->display_name)->toBe('Export user records');
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

test('role creation and editing require a display name', function (array $attributes) {
    $this->seed(UserManagementSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    $this->post(route('user-management.roles.store'), [
        'name' => 'support-manager',
        ...$attributes,
    ])->assertSessionHasErrors('display_name');

    $role = Role::create(['name' => 'support-manager', 'display_name' => 'Support Manager']);

    $this->put(route('user-management.roles.update', $role), [
        'name' => 'support-manager',
        ...$attributes,
    ])->assertSessionHasErrors('display_name');

    expect($role->fresh()->display_name)->toBe('Support Manager');
})->with([
    'missing' => [[]],
    'null' => [['display_name' => null]],
    'empty' => [['display_name' => '']],
]);
