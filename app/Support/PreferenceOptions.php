<?php

namespace App\Support;

use App\Models\PreferenceCountry;
use App\Models\PreferenceFormat;
use App\Models\PreferenceLanguage;
use App\Models\PreferenceTimezone;
use App\Models\UserPreference;
use App\Settings\SystemSettings;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class PreferenceOptions
{
    /** @var list<string> */
    public const FIELDS = [
        'country_preference_id',
        'timezone_preference_id',
        'language_preference_id',
        'number_format_preference_id',
        'date_format_preference_id',
        'time_format_preference_id',
    ];

    /**
     * @return array{
     *     countries: array<int, array<string, mixed>>,
     *     timezones: array<int, array<string, mixed>>,
     *     languages: array<int, array<string, mixed>>,
     *     numberFormats: array<int, array<string, mixed>>,
     *     dateFormats: array<int, array<string, mixed>>,
     *     timeFormats: array<int, array<string, mixed>>
     * }
     */
    public static function forForms(): array
    {
        return [
            'countries' => self::simpleOptions(PreferenceCountry::query()->orderBy('display_name')->get()),
            'timezones' => self::simpleOptions(PreferenceTimezone::query()->orderBy('display_name')->get()),
            'languages' => self::simpleOptions(PreferenceLanguage::query()->orderBy('display_name')->get()),
            'numberFormats' => self::formatOptions(PreferenceFormat::query()->type(PreferenceFormat::TYPE_NUMBER)->orderBy('display_name')->get()),
            'dateFormats' => self::formatOptions(PreferenceFormat::query()->type(PreferenceFormat::TYPE_DATE)->orderBy('display_name')->get()),
            'timeFormats' => self::formatOptions(PreferenceFormat::query()->type(PreferenceFormat::TYPE_TIME)->orderBy('display_name')->get()),
        ];
    }

    /**
     * @return array{
     *     countries: array<int, array<string, mixed>>,
     *     timezones: array<int, array<string, mixed>>,
     *     languages: array<int, array<string, mixed>>,
     *     formats: array{number: array<int, array<string, mixed>>, date: array<int, array<string, mixed>>, time: array<int, array<string, mixed>>}
     * }
     */
    public static function forIndex(): array
    {
        return [
            'countries' => self::simpleOptions(PreferenceCountry::query()->orderBy('display_name')->get()),
            'timezones' => self::simpleOptions(PreferenceTimezone::query()->orderBy('display_name')->get()),
            'languages' => self::simpleOptions(PreferenceLanguage::query()->orderBy('display_name')->get()),
            'formats' => [
                'number' => self::formatOptions(PreferenceFormat::query()->type(PreferenceFormat::TYPE_NUMBER)->orderBy('display_name')->get()),
                'date' => self::formatOptions(PreferenceFormat::query()->type(PreferenceFormat::TYPE_DATE)->orderBy('display_name')->get()),
                'time' => self::formatOptions(PreferenceFormat::query()->type(PreferenceFormat::TYPE_TIME)->orderBy('display_name')->get()),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function defaults(SystemSettings $settings): array
    {
        return [
            'country_preference_id' => $settings->default_country_preference_id,
            'timezone_preference_id' => $settings->default_timezone_preference_id,
            'language_preference_id' => $settings->default_language_preference_id,
            'number_format_preference_id' => $settings->default_number_format_preference_id,
            'date_format_preference_id' => $settings->default_date_format_preference_id,
            'time_format_preference_id' => $settings->default_time_format_preference_id,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function values(?UserPreference $preferences, SystemSettings $settings): array
    {
        $defaults = self::defaults($settings);

        if ($preferences === null) {
            return $defaults;
        }

        return [
            'country_preference_id' => $preferences->country_preference_id,
            'timezone_preference_id' => $preferences->timezone_preference_id,
            'language_preference_id' => $preferences->language_preference_id,
            'number_format_preference_id' => $preferences->number_format_preference_id,
            'date_format_preference_id' => $preferences->date_format_preference_id,
            'time_format_preference_id' => $preferences->time_format_preference_id,
        ];
    }

    /**
     * @param  EloquentCollection<int, PreferenceCountry|PreferenceTimezone|PreferenceLanguage>  $preferences
     * @return array<int, array{id: int, name: string, identifier: string, display_name: string, short_code: string}>
     */
    private static function simpleOptions(EloquentCollection $preferences): array
    {
        return $preferences
            ->map(fn (PreferenceCountry|PreferenceTimezone|PreferenceLanguage $preference) => [
                'id' => $preference->id,
                'name' => $preference->name,
                'identifier' => $preference->identifier,
                'display_name' => $preference->display_name,
                'short_code' => $preference->short_code,
            ])
            ->all();
    }

    /**
     * @param  EloquentCollection<int, PreferenceFormat>  $formats
     * @return array<int, array{id: int, type: string, name: string, display_name: string, format: string, example: string}>
     */
    private static function formatOptions(EloquentCollection $formats): array
    {
        return $formats
            ->map(fn (PreferenceFormat $format) => [
                'id' => $format->id,
                'type' => $format->type,
                'name' => $format->name,
                'display_name' => $format->display_name,
                'format' => $format->format,
                'example' => $format->example,
            ])
            ->all();
    }
}
