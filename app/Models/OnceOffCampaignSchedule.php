<?php

namespace App\Models;

use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property int $channel_id
 * @property int $attempt_number
 * @property int $last_contact_id
 * @property string $timezone
 * @property CommunicationWorkStatus $status
 * @property Carbon $scheduled_at
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $dispatch_expires_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read OnceOffCampaign $campaign
 * @property-read CommunicationChannel $channel
 */
class OnceOffCampaignSchedule extends Model
{
    use HasPublicId;

    protected $fillable = ['attempt_number', 'scheduled_at', 'channel_id', 'timezone', 'status', 'last_contact_id', 'dispatched_at', 'dispatch_expires_at', 'started_at', 'completed_at'];

    protected $hidden = ['id', 'campaign_id', 'channel_id'];

    protected $attributes = ['status' => CommunicationWorkStatus::Pending->value, 'timezone' => 'UTC', 'last_contact_id' => 0];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer', 'last_contact_id' => 'integer', 'scheduled_at' => 'datetime',
            'status' => CommunicationWorkStatus::class, 'dispatched_at' => 'datetime', 'dispatch_expires_at' => 'datetime',
            'started_at' => 'datetime', 'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OnceOffCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OnceOffCampaign::class, 'campaign_id');
    }

    /** @return BelongsTo<CommunicationChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class, 'channel_id');
    }
}
