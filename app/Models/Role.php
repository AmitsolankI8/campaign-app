<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $short_note
 * @property string $guard_name
 */
class Role extends SpatieRole
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'display_name',
        'short_note',
        'guard_name',
    ];
}
