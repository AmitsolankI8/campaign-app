<?php

namespace Database\Seeders;

use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Support\PreferenceRegistry;
use Illuminate\Database\Seeder;

class PreferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PreferenceRegistry::countries() as $country) {
            PreferenceCountry::query()->updateOrCreate(['name' => $country['name']], $country);
        }

        foreach (PreferenceRegistry::timezones() as $timezone) {
            PreferenceTimezone::query()->updateOrCreate(['name' => $timezone['name']], $timezone);
        }

        foreach (PreferenceRegistry::languages() as $language) {
            PreferenceLanguage::query()->updateOrCreate(['name' => $language['name']], $language);
        }

        foreach (PreferenceRegistry::formats() as $format) {
            PreferenceFormat::query()->updateOrCreate(
                ['type' => $format['type'], 'name' => $format['name']],
                $format,
            );
        }
    }
}
