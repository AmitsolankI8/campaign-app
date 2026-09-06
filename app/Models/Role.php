<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string|null $short_note
 * @property string $guard_name
 */
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'display_name',
        'short_note',
        'guard_name',
    ];
}
