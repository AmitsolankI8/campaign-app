<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class SystemSettingsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::groups()['System settings'] as $attributes) {
            Permission::query()->updateOrCreate(
                ['name' => $attributes['name'], 'guard_name' => 'web'],
                $attributes,
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->where('name', 'admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo(['system-settings.view', 'system-settings.edit']);
    }
}
