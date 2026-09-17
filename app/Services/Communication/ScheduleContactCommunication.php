<?php

namespace App\Services\Communication;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Enums\ScheduledCommunicationSource;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\CommunicationChannel;
use App\Models\OnceOffCampaignContact;
use App\Models\OnceOffCampaignSchedule;
use App\Models\ScheduledCommunication;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScheduleContactCommunication
{
    public function __construct(private DispatchCommunicationWork $dispatch, private CompleteCampaignExecution $complete) {}

    public function create(
        OnceOffCampaign $campaign,
        OnceOffCampaignContact $contact,
        string $channel,
        CarbonInterface $scheduledAt,
        string $timezone,
        ScheduledCommunicationSource $source = ScheduledCommunicationSource::CustomerCallback,
        ?OnceOffCampaignSchedule $schedule = null,
        ?CarbonInterface $expiresAt = null,
    ): ScheduledCommunication {
        $this->validateTime($scheduledAt, $timezone, $expiresAt);

        return DB::transaction(function () use ($campaign, $contact, $channel, $scheduledAt, $timezone, $source, $schedule, $expiresAt): ScheduledCommunication {
            $campaign = $this->lockCampaign($campaign->id);
            $contact = $campaign->contacts()->findOrFail($contact->id);
            $schedule = $schedule ? $campaign->schedules()->findOrFail($schedule->id) : null;
            $channelRecord = CommunicationChannel::query()->where('code', $channel)->where('is_active', true)->firstOrFail();
            $normalKey = $schedule ? GenerateCampaignCommunications::key($schedule->public_id, $contact->public_id) : null;
            $normal = $normalKey ? Communication::query()->where('idempotency_key', $normalKey)->lockForUpdate()->first() : null;
            $existing = $normalKey ? ScheduledCommunication::query()->where('idempotency_key', $normalKey)->lockForUpdate()->first() : null;
            $replaces = $normalKey !== null
                && (! $normal || ($normal->status === CommunicationStatus::Pending && ! $normal->attempts()->exists()))
                && (! $existing || $existing->status === CommunicationWorkStatus::Pending);
            if ($replaces && $existing) {
                if ($existing->channel_id !== $channelRecord->id) {
                    throw ValidationException::withMessages(['channel' => __('Reschedule the existing callback using its current channel.')]);
                }

                return $this->updateTime($existing, $scheduledAt, $timezone, $expiresAt);
            }
            $publicId = (string) Str::ulid();
            $callback = new ScheduledCommunication;
            $callback->public_id = $publicId;
            $callback->fill([
                'campaign_id' => $campaign->id, 'contact_id' => $contact->id,
                'campaign_schedule_id' => $schedule?->id, 'attempt_number' => $schedule?->attempt_number,
                'channel_id' => $channelRecord->id, 'source' => $source,
                'replaces_attempt' => $replaces,
                'idempotency_key' => $replaces ? $normalKey : 'callback:'.$publicId,
                'scheduled_at' => $scheduledAt->copy()->utc(), 'timezone' => $timezone,
                'expires_at' => $expiresAt?->copy()->utc(),
            ])->save();
            if ($replaces && $normal) {
                $normal->update([
                    'scheduled_communication_id' => $callback->id, 'channel_id' => $callback->channel_id,
                    'scheduled_at' => $callback->scheduled_at, 'next_attempt_at' => $callback->scheduled_at,
                    'dispatch_expires_at' => null,
                ]);
            }
            DB::afterCommit(fn () => $this->dispatch->callback($callback->id));

            return $callback;
        });
    }

    public function reschedule(ScheduledCommunication $callback, CarbonInterface $scheduledAt, string $timezone, ?CarbonInterface $expiresAt = null): ScheduledCommunication
    {
        $this->validateTime($scheduledAt, $timezone, $expiresAt);

        return DB::transaction(function () use ($callback, $scheduledAt, $timezone, $expiresAt): ScheduledCommunication {
            $this->lockCampaign($callback->campaign_id);
            $callback = ScheduledCommunication::query()->where('campaign_id', $callback->campaign_id)->lockForUpdate()->findOrFail($callback->id);

            return $this->updateTime($callback, $scheduledAt, $timezone, $expiresAt);
        });
    }

    public function cancel(ScheduledCommunication $callback): void
    {
        DB::transaction(function () use ($callback): void {
            $this->lockCampaign($callback->campaign_id);
            $callback = ScheduledCommunication::query()->where('campaign_id', $callback->campaign_id)->lockForUpdate()->findOrFail($callback->id);
            $this->assertUnstarted($callback);
            $callback->update(['status' => CommunicationWorkStatus::Cancelled, 'version' => $callback->version + 1, 'completed_at' => now()]);
            Communication::query()->where('scheduled_communication_id', $callback->id)->where('status', CommunicationStatus::Pending)
                ->update(['status' => CommunicationStatus::Cancelled, 'error_code' => 'callback_cancelled']);
            DB::afterCommit(fn () => $this->complete->handle($callback->campaign_id));
        });
    }

    private function updateTime(ScheduledCommunication $callback, CarbonInterface $scheduledAt, string $timezone, ?CarbonInterface $expiresAt): ScheduledCommunication
    {
        $this->assertUnstarted($callback);
        $callback->update([
            'scheduled_at' => $scheduledAt->copy()->utc(), 'timezone' => $timezone,
            'expires_at' => $expiresAt?->copy()->utc(), 'version' => $callback->version + 1,
            'status' => CommunicationWorkStatus::Pending, 'dispatch_expires_at' => null,
        ]);
        Communication::query()->where('scheduled_communication_id', $callback->id)->update([
            'scheduled_at' => $callback->scheduled_at, 'next_attempt_at' => $callback->scheduled_at,
            'dispatch_expires_at' => null,
        ]);
        DB::afterCommit(fn () => $this->dispatch->callback($callback->id));

        return $callback;
    }

    private function assertUnstarted(ScheduledCommunication $callback): void
    {
        $communication = Communication::query()->where('scheduled_communication_id', $callback->id)->lockForUpdate()->first();
        if (! in_array($callback->status, [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing], true)
            || ($communication && ($communication->status !== CommunicationStatus::Pending || $communication->attempts()->exists()))) {
            throw ValidationException::withMessages(['callback' => __('This callback has already started or finished.')]);
        }
    }

    private function lockCampaign(int $id): OnceOffCampaign
    {
        $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($id);
        if (! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running, CampaignStatus::Paused], true)) {
            throw ValidationException::withMessages(['campaign' => __('Callbacks require a launched, running, or paused campaign.')]);
        }

        return $campaign;
    }

    private function validateTime(CarbonInterface $scheduledAt, string $timezone, ?CarbonInterface $expiresAt): void
    {
        Validator::make(['timezone' => $timezone], ['timezone' => ['required', 'timezone']])->validate();
        if ($expiresAt && $expiresAt->lessThanOrEqualTo($scheduledAt)) {
            throw ValidationException::withMessages(['expires_at' => __('Expiry must be after the scheduled time.')]);
        }
    }
}
