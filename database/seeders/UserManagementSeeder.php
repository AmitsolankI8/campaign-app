<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserManagementSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionRegistry::groups())
            ->flatten(1)
            ->map(function (array $attributes): Permission {
                return Permission::query()->updateOrCreate(
                    ['name' => $attributes['name'], 'guard_name' => 'web'],
                    $attributes,
                );
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::query()->updateOrCreate(['name' => 'admin', 'guard_name' => 'web'], [
            'display_name' => 'Administrator',
            'short_note' => 'Full access to application administration.',
        ]);
        $adminRole->syncPermissions($permissions);

        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => 'password',
            ],
        );

        $adminUser->syncRoles([$adminRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
