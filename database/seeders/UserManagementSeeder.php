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
                /** @var Permission $permission */
                $permission = Permission::findOrCreate($attributes['name']);
                $permission->update([
                    'display_name' => $attributes['display_name'],
                    'short_note' => $attributes['short_note'],
                ]);

                return $permission;
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var Role $adminRole */
        $adminRole = Role::findOrCreate('admin');
        $adminRole->update([
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
