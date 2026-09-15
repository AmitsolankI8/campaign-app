<?php

namespace App\Models\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\OnceOffCampaignContact;
use App\Models\OnceOffCampaignContactImport;
use App\Models\OnceOffCampaignSchedule;
use Database\Factories\Campaigns\OnceOffCampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $short_note
 * @property CampaignType $campaign_type
 * @property CampaignStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OnceOffCampaignSchedule|null $firstSchedule
 */
class OnceOffCampaign extends Campaign
{
    /** @use HasFactory<OnceOffCampaignFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'campaign_type' => CampaignType::OnceOff->value,
        'status' => CampaignStatus::DEFAULT,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('once_off', function (Builder $query): void {
            $query->where($query->qualifyColumn('campaign_type'), CampaignType::OnceOff->value);
        });

        static::saving(function (self $campaign): void {
            if ($campaign->campaign_type !== CampaignType::OnceOff) {
                throw new LogicException('OnceOffCampaign must use the once-off campaign type.');
            }
        });
    }

    /** @return HasMany<OnceOffCampaignContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(OnceOffCampaignContact::class, 'campaign_id');
    }

    /** @return HasMany<OnceOffCampaignContactImport, $this> */
    public function contactImports(): HasMany
    {
        return $this->hasMany(OnceOffCampaignContactImport::class, 'campaign_id');
    }

    /** @return HasOne<OnceOffCampaignSchedule, $this> */
    public function firstSchedule(): HasOne
    {
        return $this->firstOnceOffSchedule();
    }

    /** @return HasMany<OnceOffCampaignSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(OnceOffCampaignSchedule::class, 'campaign_id');
    }
}
