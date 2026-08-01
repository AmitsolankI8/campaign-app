<?php

namespace App\Support;

use Illuminate\Support\Arr;

class PermissionRegistry
{
    /**
     * @return array<string, array<int, array{name: string, label: string}>>
     */
    public static function groups(): array
    {
        return [
            'Users' => [
                ['name' => 'users.view', 'label' => 'View users'],
                ['name' => 'users.create', 'label' => 'Create users'],
                ['name' => 'users.edit', 'label' => 'Edit users'],
                ['name' => 'users.delete', 'label' => 'Delete users'],
            ],
            'Roles' => [
                ['name' => 'roles.view', 'label' => 'View roles'],
                ['name' => 'roles.create', 'label' => 'Create roles'],
                ['name' => 'roles.edit', 'label' => 'Edit roles'],
                ['name' => 'roles.delete', 'label' => 'Delete roles'],
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
