<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system.display_name', config('app.name', 'Campaign App'));
    }

    public function down(): void
    {
        $this->migrator->delete('system.display_name');
    }
};
