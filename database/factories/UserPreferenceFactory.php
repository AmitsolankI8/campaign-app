<?php

namespace Database\Factories;

use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\User;
use App\Models\UserPreference;
use App\Settings\SystemSettings;
use App\Support\PreferenceOptions;
use App\Support\PreferenceRegistry;
use Database\Seeders\PreferenceSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Throwable;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $this->ensurePreferencesExist();

        return [
            'user_id' => User::factory()->withoutAfterCreating(),
            ...$this->defaultPreferenceIds(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function defaultPreferenceIds(): array
    {
        try {
            $defaults = PreferenceOptions::defaults(app(SystemSettings::class));

            if ($this->defaultsExist($defaults)) {
                return $defaults;
            }
        } catch (Throwable) {
        }

        $defaults = PreferenceRegistry::defaults();

        return [
            'country_preference_id' => (int) PreferenceCountry::query()->where('name', $defaults['country'])->value('id'),
            'timezone_preference_id' => (int) PreferenceTimezone::query()->where('name', $defaults['timezone'])->value('id'),
            'language_preference_id' => (int) PreferenceLanguage::query()->where('name', $defaults['language'])->value('id'),
            'number_format_preference_id' => $this->formatId(PreferenceFormat::TYPE_NUMBER, $defaults['number']),
            'date_format_preference_id' => $this->formatId(PreferenceFormat::TYPE_DATE, $defaults['date']),
            'time_format_preference_id' => $this->formatId(PreferenceFormat::TYPE_TIME, $defaults['time']),
        ];
    }

    /**
     * @param  array<string, int>  $defaults
     */
    private function defaultsExist(array $defaults): bool
    {
        return PreferenceCountry::query()->whereKey($defaults['country_preference_id'])->exists()
            && PreferenceTimezone::query()->whereKey($defaults['timezone_preference_id'])->exists()
            && PreferenceLanguage::query()->whereKey($defaults['language_preference_id'])->exists()
            && PreferenceFormat::query()->type(PreferenceFormat::TYPE_NUMBER)->whereKey($defaults['number_format_preference_id'])->exists()
            && PreferenceFormat::query()->type(PreferenceFormat::TYPE_DATE)->whereKey($defaults['date_format_preference_id'])->exists()
            && PreferenceFormat::query()->type(PreferenceFormat::TYPE_TIME)->whereKey($defaults['time_format_preference_id'])->exists();
    }

    private function ensurePreferencesExist(): void
    {
        if (PreferenceCountry::query()->count() >= count(PreferenceRegistry::countries())
            && PreferenceTimezone::query()->count() >= count(PreferenceRegistry::timezones())
            && PreferenceLanguage::query()->count() >= count(PreferenceRegistry::languages())
            && PreferenceFormat::query()->count() >= count(PreferenceRegistry::formats())) {
            return;
        }

        app(PreferenceSeeder::class)->run();
    }

    private function formatId(string $type, string $name): int
    {
        return (int) PreferenceFormat::query()
            ->type($type)
            ->where('name', $name)
            ->value('id');
    }
}
