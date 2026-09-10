<?php

use App\Models\Permission;
use App\Models\PreferenceCountry;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\PreferenceRegistry;
use Database\Seeders\CountryUsersSeeder;
use Database\Seeders\PreferenceSeeder;
use Database\Seeders\UserManagementSeeder;
use Illuminate\Database\QueryException;

test('custom role and permission metadata preserves Spatie name based APIs', function () {
    /** @var Permission $permission */
    $permission = Permission::factory()->fromRegistry('users.view')->create([
        'short_note' => 'View the user list.',
    ]);

    /** @var Role $role */
    $role = Role::factory()->create([
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
    $record = $model::factory()->make(['name' => 'example', ...$attributes]);

    if (! array_key_exists('display_name', $attributes)) {
        unset($record->display_name);
    }

    expect(fn () => $record->save())
        ->toThrow(QueryException::class);
})->with([
    'role missing' => [Role::class, []],
    'role null' => [Role::class, ['display_name' => null]],
    'permission missing' => [Permission::class, []],
    'permission null' => [Permission::class, ['display_name' => null]],
]);

test('creating roles and permissions preserves explicit display names', function () {
    $role = Role::factory()->create([
        'name' => 'support-manager',
        'display_name' => 'Support team lead',
    ]);
    $permission = Permission::factory()->create([
        'name' => 'users.export',
        'display_name' => 'Export user records',
    ]);

    expect($role->fresh()->display_name)->toBe('Support team lead')
        ->and($permission->fresh()->display_name)->toBe('Export user records');
});

test('users and roles generate public ids and use them for route keys', function () {
    $user = User::factory()->create(['public_id' => null]);
    $role = Role::factory()->create(['public_id' => null]);

    expect($user->public_id)->toMatch('/^[0-7][0-9a-hjkmnp-tv-z]{25}$/')
        ->and($role->public_id)->toMatch('/^[0-7][0-9a-hjkmnp-tv-z]{25}$/')
        ->and($user->getRouteKeyName())->toBe('public_id')
        ->and($role->getRouteKeyName())->toBe('public_id')
        ->and($user->getRouteKey())->toBe($user->public_id)
        ->and($role->getRouteKey())->toBe($role->public_id)
        ->and($user->toArray())->toHaveKey('public_id')
        ->and($user->toArray())->not->toHaveKey('id')
        ->and($role->toArray())->toHaveKey('public_id')
        ->and($role->toArray())->not->toHaveKey('id');
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
    $adminUser = User::where('email', 'admin@example.com')->firstOrFail();
    $adminPublicId = $adminUser->public_id;
    $adminUser->update(['first_name' => 'Existing', 'password' => 'changed-password']);
    $password = $adminUser->password;
    $this->seed(UserManagementSeeder::class);

    /** @var Role $role */
    $role = Role::findByName('admin');
    /** @var Permission $permission */
    $permission = Permission::findByName('users.view');

    expect($role->display_name)->toBe('Administrator')
        ->and($role->hasPermissionTo('users.view'))->toBeTrue()
        ->and($permission->display_name)->toBe('View users')
        ->and($permission->short_note)->not->toBeEmpty()
        ->and(Permission::count())->toBe(count(PermissionRegistry::names()))
        ->and(Role::count())->toBe(2)
        ->and(User::count())->toBe(2)
        ->and($adminUser->fresh()->first_name)->toBe('Existing')
        ->and($adminUser->fresh()->password)->toBe($password)
        ->and($adminUser->fresh()->public_id)->toBe($adminPublicId)
        ->and($role->public_id)->not->toBeEmpty();

    $userRole = Role::findByName('user');
    $user = User::where('email', 'user@example.com')->firstOrFail();

    expect($userRole->display_name)->toBe('User')
        ->and($userRole->hasPermissionTo('users.view'))->toBeTrue()
        ->and($userRole->hasPermissionTo('roles.view'))->toBeTrue()
        ->and($user->first_name)->toBe('User')
        ->and($user->last_name)->toBe('User')
        ->and($user->public_id)->not->toBeEmpty()
        ->and($userRole->public_id)->not->toBeEmpty()
        ->and($user->hasRole($userRole))->toBeTrue();
});

test('country users seeder creates role users with country preferences', function () {
    $this->seed(PreferenceSeeder::class);
    $this->seed(UserManagementSeeder::class);
    $this->seed(CountryUsersSeeder::class);
    $this->seed(CountryUsersSeeder::class);

    $countries = PreferenceCountry::query()->pluck('display_name', 'name');
    $registeredCountryNames = collect(PreferenceRegistry::countries())->pluck('name');

    expect($countries->keys()->all())->toEqualCanonicalizing($registeredCountryNames->all());

    foreach ($countries as $countryName => $displayName) {
        $user = User::where('email', "{$countryName}-user@example.com")->firstOrFail();

        expect($user->first_name)->toBe($displayName)
            ->and($user->last_name)->toBe('User')
            ->and($user->hasRole('user'))->toBeTrue()
            ->and($user->preferences->country->name)->toBe($countryName);
    }

    expect(User::where('email', 'like', '%-user@example.com')->count())->toBe($registeredCountryNames->count());
});

test('role and permission factories create complete distinct records', function (string $model) {
    $records = $model::factory()->count(3)->create();

    expect($records->pluck('name')->unique())->toHaveCount(3);

    foreach ($records as $record) {
        expect($record->fresh()->display_name)->not->toBeEmpty()
            ->and($record->guard_name)->toBe('web');
    }
})->with([
    'role' => [Role::class],
    'permission' => [Permission::class],
]);

test('permission factory uses canonical registry metadata', function () {
    foreach (PermissionRegistry::groups() as $permissions) {
        foreach ($permissions as $attributes) {
            $permission = Permission::factory()->fromRegistry($attributes['name'])->create();

            expect($permission->name)->toBe($attributes['name'])
                ->and($permission->display_name)->toBe($attributes['display_name'])
                ->and($permission->short_note)->toBe($attributes['short_note']);
        }
    }
});

test('permission factory rejects unknown registry names', function () {
    expect(fn () => Permission::factory()->fromRegistry('unknown.permission'))
        ->toThrow(InvalidArgumentException::class);
});

test('role creation and editing require a display name', function (array $attributes) {
    $this->seed(UserManagementSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    $this->post(route('user-management.roles.store'), [
        'name' => 'support-manager',
        ...$attributes,
    ])->assertSessionHasErrors('display_name');

    $role = Role::factory()->create(['name' => 'support-manager']);

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
