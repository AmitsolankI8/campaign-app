<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Settings\SystemSettings;
use App\Support\PermissionRegistry;
use App\Support\PreferenceOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class UserManagementSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionRegistry::names())
            ->map(function (string $name): Permission {
                $attributes = Permission::factory()->fromRegistry($name)->make()->getAttributes();

                return Permission::query()->updateOrCreate(
                    ['name' => $attributes['name'], 'guard_name' => 'web'],
                    $attributes,
                );
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::query()->firstOrNew(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->fill(Role::factory()->make([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'short_note' => 'Full access to application administration.',
        ])->getAttributes());
        $adminRole->public_id ??= (string) Str::ulid();
        $adminRole->save();
        $adminRole->syncPermissions($permissions);

        $userRole = Role::query()->firstOrNew(['name' => 'user', 'guard_name' => 'web']);
        $userRole->fill(Role::factory()->make([
            'name' => 'user',
            'display_name' => 'User',
            'short_note' => 'List access for users and roles.',
        ])->getAttributes());
        $userRole->public_id ??= (string) Str::ulid();
        $userRole->save();
        $userRole->syncPermissions($permissions->whereIn('name', ['users.view', 'roles.view']));

        $adminUser = User::role($adminRole)->first()
            ?? User::query()->where('email', 'admin@example.com')->first()
            ?? User::factory()->unverified()->create([
                'email' => 'admin@example.com',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'remember_token' => null,
            ]);

        $adminUser->syncRoles([$adminRole]);
        $adminUser->preferences()->updateOrCreate(
            ['user_id' => $adminUser->id],
            PreferenceOptions::defaults(app(SystemSettings::class)),
        );

        $user = User::query()->where('email', 'user@example.com')->first()
            ?? User::factory()->create([
                'email' => 'user@example.com',
                'first_name' => 'User',
                'last_name' => 'User',
            ]);

        $user->syncRoles([$userRole]);
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            PreferenceOptions::defaults(app(SystemSettings::class)),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
