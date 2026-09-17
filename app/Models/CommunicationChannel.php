<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property int $position
 */
class CommunicationChannel extends Model
{
    use HasPublicId;

    protected $fillable = ['code', 'name', 'position', 'is_active'];

    protected $hidden = ['id'];

    protected $attributes = ['is_active' => true, 'position' => 0];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    /** @return HasMany<CommunicationProvider, $this> */
    public function providers(): HasMany
    {
        return $this->hasMany(CommunicationProvider::class, 'channel_id');
    }
}
