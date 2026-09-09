<?php

namespace App\Support;

class PreferenceRegistry
{
    /**
     * @return array<int, array{name: string, identifier: string, display_name: string, short_code: string}>
     */
    public static function countries(): array
    {
        return [
            ['name' => 'united-states', 'identifier' => 'US', 'display_name' => 'United States', 'short_code' => 'US'],
            ['name' => 'india', 'identifier' => 'IN', 'display_name' => 'India', 'short_code' => 'IN'],
            ['name' => 'canada', 'identifier' => 'CA', 'display_name' => 'Canada', 'short_code' => 'CA'],
            ['name' => 'united-kingdom', 'identifier' => 'GB', 'display_name' => 'United Kingdom', 'short_code' => 'UK'],
            ['name' => 'australia', 'identifier' => 'AU', 'display_name' => 'Australia', 'short_code' => 'AU'],
        ];
    }

    /**
     * @return array<int, array{name: string, identifier: string, display_name: string, short_code: string}>
     */
    public static function timezones(): array
    {
        return [
            ['name' => 'utc', 'identifier' => 'UTC', 'display_name' => 'UTC', 'short_code' => 'UTC'],
            ['name' => 'eastern-time', 'identifier' => 'America/New_York', 'display_name' => 'Eastern Time', 'short_code' => 'ET'],
            ['name' => 'central-time', 'identifier' => 'America/Chicago', 'display_name' => 'Central Time', 'short_code' => 'CT'],
            ['name' => 'mountain-time', 'identifier' => 'America/Denver', 'display_name' => 'Mountain Time', 'short_code' => 'MT'],
            ['name' => 'pacific-time', 'identifier' => 'America/Los_Angeles', 'display_name' => 'Pacific Time', 'short_code' => 'PT'],
            ['name' => 'india-standard-time', 'identifier' => 'Asia/Kolkata', 'display_name' => 'India Standard Time', 'short_code' => 'IST'],
            ['name' => 'greenwich-mean-time', 'identifier' => 'Europe/London', 'display_name' => 'Greenwich Mean Time', 'short_code' => 'GMT'],
        ];
    }

    /**
     * @return array<int, array{name: string, identifier: string, display_name: string, short_code: string}>
     */
    public static function languages(): array
    {
        return [
            ['name' => 'english', 'identifier' => 'en', 'display_name' => 'English', 'short_code' => 'EN'],
            ['name' => 'english-us', 'identifier' => 'en_US', 'display_name' => 'English (United States)', 'short_code' => 'EN-US'],
            ['name' => 'english-india', 'identifier' => 'en_IN', 'display_name' => 'English (India)', 'short_code' => 'EN-IN'],
            ['name' => 'hindi', 'identifier' => 'hi', 'display_name' => 'Hindi', 'short_code' => 'HI'],
            ['name' => 'spanish', 'identifier' => 'es', 'display_name' => 'Spanish', 'short_code' => 'ES'],
            ['name' => 'french', 'identifier' => 'fr', 'display_name' => 'French', 'short_code' => 'FR'],
        ];
    }

    /**
     * @return array<int, array{type: string, name: string, display_name: string, format: string, example: string}>
     */
    public static function formats(): array
    {
        return [
            ['type' => 'number', 'name' => 'us-number', 'display_name' => 'US number', 'format' => 'en_US', 'example' => '1,234.56'],
            ['type' => 'number', 'name' => 'indian-number', 'display_name' => 'Indian number', 'format' => 'en_IN', 'example' => '1,23,456.78'],
            ['type' => 'number', 'name' => 'german-number', 'display_name' => 'German number', 'format' => 'de_DE', 'example' => '1.234,56'],
            ['type' => 'date', 'name' => 'iso-date', 'display_name' => 'ISO date', 'format' => 'Y-m-d', 'example' => '2026-09-09'],
            ['type' => 'date', 'name' => 'us-date', 'display_name' => 'US date', 'format' => 'm/d/Y', 'example' => '09/09/2026'],
            ['type' => 'date', 'name' => 'day-first-date', 'display_name' => 'Day-first date', 'format' => 'd/m/Y', 'example' => '09/09/2026'],
            ['type' => 'time', 'name' => 'twenty-four-hour-time', 'display_name' => '24-hour time', 'format' => 'H:i', 'example' => '17:30'],
            ['type' => 'time', 'name' => 'twelve-hour-time', 'display_name' => '12-hour time', 'format' => 'h:i A', 'example' => '05:30 PM'],
            ['type' => 'time', 'name' => 'twenty-four-hour-time-with-seconds', 'display_name' => '24-hour time with seconds', 'format' => 'H:i:s', 'example' => '17:30:45'],
        ];
    }
}
