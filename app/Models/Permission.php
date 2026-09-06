<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $short_note
 * @property string $guard_name
 */
class Permission extends SpatiePermission
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'display_name',
        'short_note',
        'guard_name',
    ];
}
