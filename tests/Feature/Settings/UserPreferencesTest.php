<?php

use App\Models\Permission;
use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\User;
use App\Models\UserPreference;
use Inertia\Testing\AssertableInertia as Assert;

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
