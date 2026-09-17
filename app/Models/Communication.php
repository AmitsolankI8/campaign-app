<?php

namespace App\Models;

use App\Enums\CommunicationStatus;
use App\Models\Concerns\HasPublicId;
use Carbon\CarbonInterface as Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property int|null $campaign_schedule_id
 * @property int $contact_id
 * @property int $channel_id
 * @property int|null $scheduled_communication_id
 * @property CommunicationStatus $status
 * @property string $idempotency_key
 * @property string $correlation_id
 * @property Carbon $scheduled_at
 * @property Carbon $next_attempt_at
 * @property int $retry_count
 * @property string|null $claim_token
 * @property Carbon|null $claim_expires_at
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $dispatch_expires_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property string|null $error_code
 * @property-read Campaigns\OnceOffCampaign $campaign
 * @property-read OnceOffCampaignContact|null $contact
 * @property-read OnceOffCampaignSchedule|null $schedule
 * @property-read ScheduledCommunication|null $scheduledCommunication
 * @property-read CommunicationChannel $channel
 */
class Communication extends Model
{
    use HasPublicId;

    protected $fillable = ['campaign_id', 'campaign_schedule_id', 'contact_id', 'channel_id', 'scheduled_communication_id', 'status', 'idempotency_key', 'correlation_id', 'scheduled_at', 'next_attempt_at', 'retry_count', 'claim_token', 'claim_expires_at', 'dispatched_at', 'dispatch_expires_at', 'sent_at', 'delivered_at', 'failed_at', 'error_code'];

    protected $hidden = ['id', 'campaign_id', 'campaign_schedule_id', 'scheduled_communication_id', 'contact_id', 'channel_id', 'claim_token'];

    protected $attributes = ['status' => CommunicationStatus::Pending->value, 'retry_count' => 0];

    protected function casts(): array
    {
        return ['id' => 'integer', 'campaign_id' => 'integer', 'campaign_schedule_id' => 'integer', 'contact_id' => 'integer', 'channel_id' => 'integer', 'scheduled_communication_id' => 'integer', 'status' => CommunicationStatus::class, 'scheduled_at' => 'datetime', 'next_attempt_at' => 'datetime', 'retry_count' => 'integer', 'claim_expires_at' => 'datetime', 'dispatched_at' => 'datetime', 'dispatch_expires_at' => 'datetime', 'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'failed_at' => 'datetime'];
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

    /** @return BelongsTo<ScheduledCommunication, $this> */
    public function scheduledCommunication(): BelongsTo
    {
        return $this->belongsTo(ScheduledCommunication::class, 'scheduled_communication_id');
    }

    /** @return BelongsTo<CommunicationChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class, 'channel_id');
    }

    /** @return HasMany<CommunicationAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(CommunicationAttempt::class, 'communication_id');
    }
}
