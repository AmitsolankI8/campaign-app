<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system.default_country_preference_id', 1);
        $this->migrator->add('system.default_timezone_preference_id', 2);
        $this->migrator->add('system.default_language_preference_id', 1);
        $this->migrator->add('system.default_number_format_preference_id', 1);
        $this->migrator->add('system.default_date_format_preference_id', 5);
        $this->migrator->add('system.default_time_format_preference_id', 9);
    }

    public function down(): void
    {
        $this->migrator->delete('system.default_country_preference_id');
        $this->migrator->delete('system.default_timezone_preference_id');
        $this->migrator->delete('system.default_language_preference_id');
        $this->migrator->delete('system.default_number_format_preference_id');
        $this->migrator->delete('system.default_date_format_preference_id');
        $this->migrator->delete('system.default_time_format_preference_id');
    }
};
