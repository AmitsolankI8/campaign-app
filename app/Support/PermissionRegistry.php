<?php

namespace App\Support;

use Illuminate\Support\Arr;

class PermissionRegistry
{
    /**
     * @return array<string, array<int, array{name: string, display_name: string, short_note: string}>>
     */
    public static function groups(): array
    {
        return [
            'Communication' => [
                ['name' => 'communication.view', 'display_name' => 'View communication settings', 'short_note' => 'View communication channels and providers.'],
                ['name' => 'communication.edit', 'display_name' => 'Edit communication settings', 'short_note' => 'Manage provider activation, credentials, and priority.'],
            ],
            'System settings' => [
                ['name' => 'system-settings.view', 'display_name' => 'View system settings', 'short_note' => 'View system-wide settings.'],
                ['name' => 'system-settings.edit', 'display_name' => 'Edit system settings', 'short_note' => 'Update system-wide settings.'],
            ],
            'Preferences' => [
                ['name' => 'preferences.view', 'display_name' => 'View preferences', 'short_note' => 'View available countries, timezones, languages, and formats.'],
            ],
            'Users' => [
                ['name' => 'users.view', 'display_name' => 'View users', 'short_note' => 'View the user list and user details.'],
                ['name' => 'users.create', 'display_name' => 'Create users', 'short_note' => 'Create new user accounts.'],
                ['name' => 'users.edit', 'display_name' => 'Edit users', 'short_note' => 'Update user details and role assignments.'],
                ['name' => 'users.delete', 'display_name' => 'Delete users', 'short_note' => 'Delete user accounts.'],
            ],
            'Roles' => [
                ['name' => 'roles.view', 'display_name' => 'View roles', 'short_note' => 'View roles and their assignments.'],
                ['name' => 'roles.create', 'display_name' => 'Create roles', 'short_note' => 'Create roles and assign permissions.'],
                ['name' => 'roles.edit', 'display_name' => 'Edit roles', 'short_note' => 'Update roles and permission assignments.'],
                ['name' => 'roles.delete', 'display_name' => 'Delete roles', 'short_note' => 'Delete roles that are not assigned to users.'],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return Arr::flatten(
            collect(self::groups())
                ->map(fn (array $permissions) => Arr::pluck($permissions, 'name'))
                ->all()
        );
    }
}
