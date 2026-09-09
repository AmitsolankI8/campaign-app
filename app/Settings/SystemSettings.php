<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemSettings extends Settings
{
    public string $display_name;

    public int $default_country_preference_id;

    public int $default_timezone_preference_id;

    public int $default_language_preference_id;

    public int $default_number_format_preference_id;

    public int $default_date_format_preference_id;

    public int $default_time_format_preference_id;

    public static function group(): string
    {
        return 'system';
    }
}
