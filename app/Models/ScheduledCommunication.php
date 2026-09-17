<?php

namespace App\Models;

use App\Enums\CommunicationWorkStatus;
use App\Enums\ScheduledCommunicationSource;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property int|null $campaign_schedule_id
 * @property int $contact_id
 * @property int $channel_id
 * @property int|null $attempt_number
 * @property int $version
 * @property string $idempotency_key
 * @property bool $replaces_attempt
 * @property Carbon $scheduled_at
 * @property string $timezone
 * @property ScheduledCommunicationSource $source
 * @property CommunicationWorkStatus $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $dispatch_expires_at
 * @property Carbon|null $completed_at
 * @property-read Campaigns\OnceOffCampaign $campaign
 * @property-read OnceOffCampaignContact $contact
 * @property-read OnceOffCampaignSchedule|null $schedule
 * @property-read CommunicationChannel $channel
 */
class ScheduledCommunication extends Model
{
    use HasPublicId;

    protected $fillable = ['campaign_id', 'campaign_schedule_id', 'contact_id', 'channel_id', 'attempt_number', 'version', 'idempotency_key', 'replaces_attempt', 'scheduled_at', 'timezone', 'source', 'status', 'expires_at', 'dispatched_at', 'dispatch_expires_at', 'completed_at'];

    protected $hidden = ['id', 'campaign_id', 'campaign_schedule_id', 'contact_id', 'channel_id'];

    protected $attributes = ['status' => CommunicationWorkStatus::Pending->value, 'version' => 1, 'replaces_attempt' => false, 'timezone' => 'UTC'];

    protected function casts(): array
    {
        return ['id' => 'integer', 'campaign_id' => 'integer', 'campaign_schedule_id' => 'integer', 'contact_id' => 'integer', 'channel_id' => 'integer', 'attempt_number' => 'integer', 'version' => 'integer', 'replaces_attempt' => 'boolean', 'scheduled_at' => 'datetime', 'source' => ScheduledCommunicationSource::class, 'status' => CommunicationWorkStatus::class, 'expires_at' => 'datetime', 'dispatched_at' => 'datetime', 'dispatch_expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    /** @return BelongsTo<Campaigns\OnceOffCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaigns\OnceOffCampaign::class, 'campaign_id');
    }

    /** @return BelongsTo<OnceOffCampaignContact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(OnceOffCampaignContact::class, 'contact_id');
    }

    /** @return BelongsTo<OnceOffCampaignSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(OnceOffCampaignSchedule::class, 'campaign_schedule_id');
    }

    /** @return BelongsTo<CommunicationChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class, 'channel_id');
    }
}
