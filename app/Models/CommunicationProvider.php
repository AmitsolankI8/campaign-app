<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $public_id
 * @property int $id
 * @property int $channel_id
 * @property string $code
 * @property string $name
 * @property int $position
 * @property list<array{key: string, label: string, type: string, secret: bool, required: bool, rules: list<string>, options?: list<array{value: string, label: string}>}> $fields
 * @property bool $is_active
 * @property-read CommunicationChannel $channel
 * @property-read CommunicationProviderAccount|null $defaultAccount
 */
class CommunicationProvider extends Model
{
    use HasPublicId;

    protected $fillable = ['channel_id', 'code', 'name', 'position', 'fields', 'is_active'];

    protected $hidden = ['id'];

    protected $attributes = ['is_active' => true, 'position' => 0];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'fields' => 'array', 'position' => 'integer'];
    }

    /** @return BelongsTo<CommunicationChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class, 'channel_id');
    }

    /** @return HasMany<CommunicationProviderAccount, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(CommunicationProviderAccount::class, 'provider_id');
    }

    /** @return HasOne<CommunicationProviderAccount, $this> */
    public function defaultAccount(): HasOne
    {
        return $this->hasOne(CommunicationProviderAccount::class, 'provider_id')->where('key', 'default');
    }
}
