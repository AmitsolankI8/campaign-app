<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Concerns\HasPublicId;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $short_note
 * @property CampaignType $campaign_type
 * @property CampaignStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasPublicId;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'campaign_type',
        'status',
        'short_note',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => CampaignStatus::DEFAULT,
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'campaign_type' => CampaignType::class,
        'status' => CampaignStatus::class,
    ];

    /** @return HasMany<OnceOffCampaignContact, $this> */
    public function onceOffContacts(): HasMany
    {
        return $this->hasMany(OnceOffCampaignContact::class);
    }

    /** @return HasMany<OnceOffCampaignContactImport, $this> */
    public function contactImports(): HasMany
    {
        return $this->hasMany(OnceOffCampaignContactImport::class);
    }
}
