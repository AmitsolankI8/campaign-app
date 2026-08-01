<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;    
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserManagementSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionRegistry::names())
            ->map(fn (string $permission) => Permission::findOrCreate($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::findOrCreate('Admin');
        $adminRole->syncPermissions($permissions);

        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
            ],
        );

        $adminUser->syncRoles([$adminRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
