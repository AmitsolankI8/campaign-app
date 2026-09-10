<?php

use App\Models\Permission;
use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Support\UserPreferences;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Cache::flush();
});

/**
 * @return array<string, int>
 */
function alternatePreferencePayload(): array
{
    return [
        'country_preference_id' => (int) PreferenceCountry::query()->where('name', 'united-states')->value('id'),
        'timezone_preference_id' => (int) PreferenceTimezone::query()->where('name', 'utc')->value('id'),
        'language_preference_id' => (int) PreferenceLanguage::query()->where('name', 'english')->value('id'),
        'number_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_NUMBER)->where('name', 'us-number')->value('id'),
        'date_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_DATE)->where('name', 'iso-date')->value('id'),
        'time_format_preference_id' => (int) PreferenceFormat::query()->type(PreferenceFormat::TYPE_TIME)->where('name', 'twenty-four-hour-time')->value('id'),
    ];
}

test('user factory creates default preferences automatically', function () {
    $user = User::factory()->create();
    $preferences = $user->preferences()->firstOrFail();
    $defaults = preferencePayload();

    expect(UserPreference::count())->toBe(1)
        ->and($preferences->only(array_keys($defaults)))->toBe($defaults);
});

test('user preference factory creates a user without duplicate preferences', function () {
    $preferences = UserPreference::factory()->create();

    expect(User::count())->toBe(1)
        ->and(UserPreference::count())->toBe(1)
        ->and($preferences->user->preferences()->whereKey($preferences)->exists())->toBeTrue();
});

test('profile updates persist selected preferences', function () {
    $user = User::factory()->create();
    $preferences = alternatePreferencePayload();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
            ...$preferences,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->preferences()->firstOrFail()->only(array_keys($preferences)))->toBe($preferences);
});

test('preferences page requires the view permission', function () {
    Permission::factory()->fromRegistry('preferences.view')->create();

    $this->get(route('preferences.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('preferences.index'))->assertForbidden();

    $user->givePermissionTo('preferences.view');
    $this->get(route('preferences.index'))->assertSuccessful();
});

test('preferences page exposes system defaults for default markers', function () {
    Permission::factory()->fromRegistry('preferences.view')->create();
    $user = User::factory()->create();
    $user->givePermissionTo('preferences.view');
    $defaults = preferencePayload();

    $this->actingAs($user)
        ->get(route('preferences.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('system-settings/Preferences')
            ->where('defaultPreferences', $defaults)
            ->where('preferences.countries', fn ($countries) => collect($countries)->contains('id', $defaults['country_preference_id']))
            ->where('preferences.timezones', fn ($timezones) => collect($timezones)->contains('id', $defaults['timezone_preference_id']))
            ->where('preferences.languages', fn ($languages) => collect($languages)->contains('id', $defaults['language_preference_id']))
            ->where('preferences.formats.number', fn ($formats) => collect($formats)->contains('id', $defaults['number_format_preference_id']))
            ->where('preferences.formats.date', fn ($formats) => collect($formats)->contains('id', $defaults['date_format_preference_id']))
            ->where('preferences.formats.time', fn ($formats) => collect($formats)->contains('id', $defaults['time_format_preference_id'])));
});

test('authenticated inertia responses share selected preference references', function () {
    $user = User::factory()->create();
    $preferences = alternatePreferencePayload();
    $user->preferences()->firstOrFail()->update($preferences);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.preferences.values', $preferences)
            ->where('auth.preferences.country.name', 'united-states')
            ->where('auth.preferences.timezone.identifier', 'UTC')
            ->where('auth.preferences.language.name', 'english')
            ->where('auth.preferences.formats.number.format', 'en_US')
            ->where('auth.preferences.formats.date.format', 'Y-m-d')
            ->where('auth.preferences.formats.date.client_format', 'YYYY-MM-DD')
            ->where('auth.preferences.formats.time.format', 'H:i')
            ->where('auth.preferences.formats.time.client_format', 'HH:mm'));
});

test('user preferences are cached and invalidated when preferences change', function () {
    $user = User::factory()->create();

    $firstPreferences = UserPreferences::forUser($user);

    expect($firstPreferences['timezone']['identifier'])->toBe('Asia/Kolkata')
        ->and(Cache::has("user-preferences:{$user->id}"))->toBeTrue();

    $user->preferences()->firstOrFail()->update(alternatePreferencePayload());

    expect(Cache::has("user-preferences:{$user->id}"))->toBeFalse();

    $updatedPreferences = UserPreferences::forUser($user);

    expect($updatedPreferences['timezone']['identifier'])->toBe('UTC')
        ->and($updatedPreferences['formats']['date']['client_format'])->toBe('YYYY-MM-DD')
        ->and($updatedPreferences['formats']['time']['client_format'])->toBe('HH:mm');
});

test('cached user preferences expire after one week', function () {
    $user = User::factory()->create();

    $firstPreferences = UserPreferences::forUser($user);
    $user->preferences()->firstOrFail()->updateQuietly(alternatePreferencePayload());

    $this->travel(6)->days();
    $cachedPreferences = UserPreferences::forUser($user);

    expect($firstPreferences['timezone']['identifier'])->toBe('Asia/Kolkata')
        ->and($cachedPreferences['timezone']['identifier'])->toBe('Asia/Kolkata');

    $this->travel(2)->days();
    $expiredPreferences = UserPreferences::forUser($user);

    expect($expiredPreferences['timezone']['identifier'])->toBe('UTC');
});

test('management indexes return created at values as utc iso strings', function () {
    Permission::factory()->fromRegistry('users.view')->create();
    Permission::factory()->fromRegistry('roles.view')->create();

    $viewer = User::factory()->create();
    $viewer->givePermissionTo(['users.view', 'roles.view']);

    $listedUser = User::factory()->create([
        'created_at' => Carbon::parse('2026-09-09 12:34:56', 'UTC'),
    ]);
    $role = Role::factory()->create([
        'display_name' => 'Support Manager',
        'created_at' => Carbon::parse('2026-09-09 23:45:01', 'UTC'),
    ]);

    $this->actingAs($viewer)
        ->get(route('user-management.users.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($users) => collect($users)->firstWhere('id', $listedUser->public_id)['created_at'] === '2026-09-09T12:34:56.000000Z'));

    $this->get(route('user-management.roles.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles.data', fn ($roles) => collect($roles)->firstWhere('id', $role->public_id)['created_at'] === '2026-09-09T23:45:01.000000Z'));
});
