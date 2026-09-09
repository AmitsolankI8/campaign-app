<?php

namespace Database\Seeders;

use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CountryUsersSeeder extends Seeder
{
    /**
     * @var array<string, array{timezone: string, language: string, number: string, date: string, time: string}>
     */
    private const COUNTRY_PREFERENCES = [
        'united-states' => ['timezone' => 'eastern-time', 'language' => 'english-us', 'number' => 'us-number', 'date' => 'us-date', 'time' => 'twelve-hour-time'],
        'india' => ['timezone' => 'india-standard-time', 'language' => 'english-india', 'number' => 'indian-number', 'date' => 'day-first-date', 'time' => 'twelve-hour-time'],
        'canada' => ['timezone' => 'eastern-time', 'language' => 'english', 'number' => 'us-number', 'date' => 'iso-date', 'time' => 'twelve-hour-time'],
        'united-kingdom' => ['timezone' => 'greenwich-mean-time', 'language' => 'english', 'number' => 'us-number', 'date' => 'day-first-date', 'time' => 'twenty-four-hour-time'],
        'australia' => ['timezone' => 'utc', 'language' => 'english', 'number' => 'us-number', 'date' => 'day-first-date', 'time' => 'twenty-four-hour-time'],
    ];

    public function run(): void
    {
        $userRole = Role::findByName('user');

        foreach (self::COUNTRY_PREFERENCES as $countryName => $preferences) {
            $country = PreferenceCountry::query()->where('name', $countryName)->firstOrFail();
            $user = User::query()->where('email', "{$countryName}-user@example.com")->first()
                ?? User::factory()->create([
                    'email' => "{$countryName}-user@example.com",
                    'first_name' => $country->display_name,
                    'last_name' => 'User',
                ]);

            $user->syncRoles([$userRole]);
            $user->preferences()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'country_preference_id' => $country->id,
                    'timezone_preference_id' => PreferenceTimezone::query()->where('name', $preferences['timezone'])->firstOrFail()->id,
                    'language_preference_id' => PreferenceLanguage::query()->where('name', $preferences['language'])->firstOrFail()->id,
                    'number_format_preference_id' => $this->formatId(PreferenceFormat::TYPE_NUMBER, $preferences['number']),
                    'date_format_preference_id' => $this->formatId(PreferenceFormat::TYPE_DATE, $preferences['date']),
                    'time_format_preference_id' => $this->formatId(PreferenceFormat::TYPE_TIME, $preferences['time']),
                ],
            );
        }
    }

    private function formatId(string $type, string $name): int
    {
        return PreferenceFormat::query()
            ->type($type)
            ->where('name', $name)
            ->firstOrFail()
            ->id;
    }
}
