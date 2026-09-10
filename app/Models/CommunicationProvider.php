<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $public_id
 * @property string $channel
 * @property string $provider
 * @property string $name
 * @property string $channel_name
 * @property int $channel_position
 * @property int $position
 * @property list<array{key: string, label: string, type: string, secret: bool, required: bool, rules: list<string>, options?: list<array{value: string, label: string}>}> $fields
 * @property bool $is_active
 * @property int $priority
 * @property array<string, string|null> $credentials
 */
class CommunicationProvider extends Model
{
    use HasPublicId;

    protected $fillable = ['channel', 'provider', 'name', 'channel_name', 'channel_position', 'position', 'fields', 'is_active', 'priority', 'credentials'];

    protected $hidden = ['credentials'];

    protected $attributes = ['is_active' => false, 'priority' => 1, 'channel_position' => 0, 'position' => 0];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'priority' => 'integer', 'credentials' => 'encrypted:array', 'fields' => 'array', 'channel_position' => 'integer', 'position' => 'integer'];
    }
}
