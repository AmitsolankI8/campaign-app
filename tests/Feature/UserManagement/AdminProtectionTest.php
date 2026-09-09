<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserManagementSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole($this->adminRole);
    $this->manager = User::factory()->create();
    $role = Role::factory()->create(['name' => 'manager']);
    foreach (['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.edit'] as $name) {
        Permission::factory()->fromRegistry($name)->create();
        $role->givePermissionTo($name);
    }
    $this->manager->assignRole($role);
    $this->adminRole->givePermissionTo(['users.view', 'users.edit']);
    $this->actingAs($this->manager);
});

test('admin cannot be assigned when creating or updating another user', function () {
    $data = ['first_name' => 'New', 'last_name' => 'User', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'roles' => ['admin'], ...preferencePayload()];
    $this->post(route('user-management.users.store'), $data)->assertSessionHasErrors('roles.0');
    expect(User::where('email', $data['email'])->exists())->toBeFalse();
    $this->put(route('user-management.users.update', $this->manager), $data)->assertSessionHasErrors('roles.0');
    expect($this->manager->fresh()->hasRole('admin'))->toBeFalse();
});

test('admin role changes are rejected without changing the profile', function (array $roles) {
    $this->actingAs($this->admin);
    $this->put(route('user-management.users.update', $this->admin), [
        'first_name' => 'Changed', 'last_name' => $this->admin->last_name, 'email' => $this->admin->email, 'roles' => $roles, ...preferencePayload(),
    ])->assertSessionHasErrors('roles');
    expect($this->admin->fresh()->first_name)->toBe($this->admin->first_name);
    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
})->with(['empty' => [[]], 'replacement' => [['manager']], 'addition' => [['admin', 'manager']]]);

test('admin profile updates preserve roles when unchanged or omitted', function (bool $includeRoles) {
    $this->actingAs($this->admin);
    $data = ['first_name' => 'Updated', 'last_name' => $this->admin->last_name, 'email' => $this->admin->email, ...preferencePayload()];
    if ($includeRoles) {
        $data['roles'] = ['admin'];
    }
    $this->put(route('user-management.users.update', $this->admin), $data)->assertSessionHasNoErrors()->assertRedirect();
    expect($this->admin->fresh()->first_name)->toBe('Updated');
    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
})->with([true, false]);

test('admin cannot be deleted from management or profile settings', function () {
    $this->delete(route('user-management.users.destroy', $this->admin))->assertUnprocessable();
    $this->assertModelExists($this->admin);
    $this->actingAs($this->admin)->delete(route('profile.destroy'), ['password' => 'password'])->assertUnprocessable();
    $this->assertModelExists($this->admin);
    $this->assertAuthenticatedAs($this->admin);
    $this->get(route('profile.edit'))->assertInertia(fn (Assert $page) => $page->where('canDeleteAccount', false));
});

test('management pages expose admin restrictions', function () {
    $this->get(route('user-management.users.index'))->assertInertia(fn (Assert $page) => $page->where('users', fn ($users) => collect($users)->every(fn ($user) => $user['can_delete'] === false)));
    $this->actingAs($this->admin)->get(route('user-management.users.edit', $this->admin))->assertInertia(fn (Assert $page) => $page->where('managedUser.roles_locked', true)->where('managedUser.can_edit', true));
});

test('logged in admin receives permission and edit access for their own list row', function () {
    $this->actingAs($this->admin)->get(route('user-management.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $this->admin->id)
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('users.edit'))
            ->where('users', fn ($users) => collect($users)->firstWhere('id', $this->admin->id)['can_edit'] === true));
});

test('other users cannot edit the admin even with direct or inherited permission', function (bool $direct) {
    if ($direct) {
        $this->manager->syncRoles([]);
        $this->manager->givePermissionTo(['users.view', 'users.edit']);
    }

    $this->get(route('user-management.users.edit', $this->admin))->assertForbidden();
    $this->put(route('user-management.users.update', $this->admin), [
        'first_name' => 'Changed', 'last_name' => $this->admin->last_name,
        'email' => 'changed@example.com', 'password' => 'new-password',
        'password_confirmation' => 'new-password', 'roles' => ['admin'],
    ])->assertForbidden();

    expect($this->admin->fresh()->only(['first_name', 'email', 'password']))
        ->toBe($this->admin->only(['first_name', 'email', 'password']));
    $this->get(route('user-management.users.index'))->assertInertia(fn (Assert $page) => $page
        ->where('users', fn ($users) => collect($users)->firstWhere('id', $this->admin->id)['can_edit'] === false));
})->with([true, false]);

test('ordinary users can still be created updated and deleted with inherited permissions', function () {
    $data = ['first_name' => 'New', 'last_name' => 'User', 'email' => 'new@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'roles' => ['manager'], ...preferencePayload()];
    $this->post(route('user-management.users.store'), $data)->assertSessionHasNoErrors();
    $user = User::where('email', $data['email'])->firstOrFail();
    $data['roles'] = [];
    $this->put(route('user-management.users.update', $user), $data)->assertSessionHasNoErrors();
    expect($user->fresh()->roles)->toHaveCount(0);
    $this->delete(route('user-management.users.destroy', $user))->assertRedirect();
    $this->assertModelMissing($user);
});

test('users without permissions cannot update or delete users', function () {
    $this->actingAs(User::factory()->create());
    $this->put(route('user-management.users.update', $this->admin), [])->assertForbidden();
    $this->delete(route('user-management.users.destroy', $this->admin))->assertForbidden();
});

test('admin role cannot be renamed', function () {
    $this->put(route('user-management.roles.update', $this->adminRole), ['name' => 'renamed', 'display_name' => 'Administrator'])->assertSessionHasErrors('name');
    expect($this->adminRole->fresh()->name)->toBe('admin');
});

test('seeding reuses the admin after their email changes', function () {
    $this->seed(UserManagementSeeder::class);
    expect(User::role('admin')->count())->toBe(1);
    expect(User::role('admin')->first()->id)->toBe($this->admin->id);
});
