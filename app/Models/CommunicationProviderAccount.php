<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $provider_id
 * @property string $key
 * @property int $priority
 * @property bool $is_active
 * @property array<string, string|null>|null $credentials
 * @property array<string, mixed>|null $settings
 * @property-read CommunicationProvider $provider
 */
class CommunicationProviderAccount extends Model
{
    use HasPublicId;

    protected $fillable = ['provider_id', 'key', 'credentials', 'settings', 'priority', 'is_active'];

    protected $hidden = ['id', 'provider_id', 'credentials'];

    protected $attributes = ['key' => 'default', 'is_active' => false, 'priority' => 1];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'settings' => 'array', 'priority' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<CommunicationProvider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(CommunicationProvider::class, 'provider_id');
    }
}
