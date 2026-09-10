<?php

use App\Models\Permission;
use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\Role;
use App\Models\User;
use App\Settings\SystemSettings;
use Database\Seeders\UserManagementSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Permission::factory()->fromRegistry('system-settings.view')->create();
    Permission::factory()->fromRegistry('system-settings.edit')->create();
});

/**
 * @return array<string, int>
 */
function alternateDefaultPreferencePayload(): array
{
    return [
        'default_country_preference_id' => (int) PreferenceCountry::query()->where('name', 'united-states')->value('id'),
        'default_timezone_preference_id' => (int) PreferenceTimezone::query()->where('name', 'utc')->value('id'),
        'default_language_preference_id' => (int) PreferenceLanguage::query()->where('name', 'english')->value('id'),
        'default_number_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_NUMBER)->where('name', 'us-number')->value('id'),
        'default_date_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_DATE)->where('name', 'iso-date')->value('id'),
        'default_time_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_TIME)->where('name', 'twenty-four-hour-time')->value('id'),
    ];
}

test('user management seeding assigns registered system settings permissions to admins', function () {
    Permission::factory()->fromRegistry('users.view')->create();
    $role = Role::factory()->create(['name' => 'admin']);
    $role->givePermissionTo('users.view');
    $this->seed(UserManagementSeeder::class);
    $this->seed(UserManagementSeeder::class);
    expect($role->fresh()->hasAllPermissions(['users.view', 'system-settings.view', 'system-settings.edit']))->toBeTrue();
});

test('guests cannot access system settings', function () {
    $this->get(route('system-settings.edit'))->assertRedirect(route('login'));
    $this->put(route('system-settings.update'), [])->assertRedirect(route('login'));
});

test('missing grants deny access and cannot change settings', function () {
    $original = app(SystemSettings::class)->default_country_preference_id;
    $this->actingAs(User::factory()->create())
        ->get(route('system-settings.edit'))->assertForbidden();
    $this->put(route('system-settings.update'), alternateDefaultPreferencePayload())->assertForbidden();
    expect(app(SystemSettings::class)->refresh()->default_country_preference_id)->toBe($original);
});

test('viewers can read but cannot update system settings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system-settings.view');
    $this->actingAs($user)->get(route('system-settings.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('system-settings/System')
            ->where('auth.permissions', ['system-settings.view'])
            ->where('settings.default_country_preference_id', app(SystemSettings::class)->default_country_preference_id));
    $this->put(route('system-settings.update'), alternateDefaultPreferencePayload())->assertForbidden();
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

    $settings = alternateDefaultPreferencePayload();

    $this->actingAs($user)->put(route('system-settings.update'), $settings)
        ->assertSessionHasNoErrors()->assertRedirect(route('system-settings.edit'));
    $this->assertDatabaseHas('settings', [
        'group' => 'system', 'name' => 'default_country_preference_id', 'payload' => json_encode($settings['default_country_preference_id']),
    ]);
    $this->get(route('system-settings.edit'))->assertInertia(fn (Assert $page) => $page
        ->where('settings.default_country_preference_id', $settings['default_country_preference_id'])
        ->where('name', config('app.name'))
        ->where('auth.permissions', ['system-settings.view', 'system-settings.edit']));

    if ($inherited) {
        $role->revokePermissionTo('system-settings.edit');
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->put(route('system-settings.update'), preferencePayload('default_'))->assertForbidden();
    }
})->with([false, true]);

test('editing requires view access too', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('system-settings.edit');
    $this->actingAs($user)->get(route('system-settings.edit'))->assertForbidden();
    $this->put(route('system-settings.update'), preferencePayload('default_'))->assertForbidden();
});

test('invalid system default preferences are rejected', function (callable $override, string $errorKey) {
    $user = User::factory()->create();
    $user->givePermissionTo(['system-settings.view', 'system-settings.edit']);
    $original = app(SystemSettings::class)->default_country_preference_id;
    $this->actingAs($user)->put(route('system-settings.update'), [
        ...preferencePayload('default_'),
        ...$override(),
    ])
        ->assertSessionHasErrors($errorKey);
    expect(app(SystemSettings::class)->refresh()->default_country_preference_id)->toBe($original);
})->with([
    'missing country' => [fn () => ['default_country_preference_id' => null], 'default_country_preference_id'],
    'unknown timezone' => [fn () => ['default_timezone_preference_id' => 999999], 'default_timezone_preference_id'],
    'wrong format type' => [fn () => ['default_number_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_DATE)->value('id')], 'default_number_format_preference_id'],
]);
