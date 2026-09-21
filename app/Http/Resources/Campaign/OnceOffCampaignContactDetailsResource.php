<?php

namespace App\Http\Resources\Campaign;

use App\Models\Communication;
use App\Models\CommunicationAttempt;
use App\Models\OnceOffCampaignContact;
use App\Models\OnceOffCampaignSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnceOffCampaignContact */
class OnceOffCampaignContactDetailsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $communications = $this->communications
            ->whereNotNull('campaign_schedule_id')
            ->keyBy('campaign_schedule_id');
        $schedules = $this->campaign->schedules->sortBy('attempt_number')->values();

        return [
            'id' => $this->public_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'number' => $this->number,
            'email' => $this->email,
            'created_at' => $this->created_at?->toJSON(),
            'schedule_count' => $schedules->count(),
            'schedules' => $schedules->map(fn (OnceOffCampaignSchedule $schedule): array => [
                'id' => $schedule->public_id,
                'schedule_number' => $schedule->attempt_number,
                'channel' => [
                    'key' => $schedule->channel->code,
                    'label' => $schedule->channel->name,
                ],
                'status' => $schedule->status->toArray(),
                'scheduled_at' => $schedule->scheduled_at->toJSON(),
                'communication' => $this->communicationPayload($communications->get($schedule->id)),
            ])->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function communicationPayload(?Communication $communication): ?array
    {
        if ($communication === null) {
            return null;
        }

        return [
            'id' => $communication->public_id,
            'type' => $this->attemptType($communication),
            'channel' => [
                'key' => $communication->channel->code,
                'label' => $communication->channel->name,
            ],
            'status' => $communication->status->toArray(),
            'scheduled_at' => $communication->scheduled_at->toJSON(),
            'provider_attempts' => $communication->attempts
                ->sortBy('attempt_number')
                ->values()
                ->map(fn (CommunicationAttempt $attempt): array => [
                    'id' => $attempt->public_id,
                    'attempt_number' => $attempt->attempt_number,
                    'provider' => $attempt->providerAccount->provider->name,
                    'status' => $attempt->status->toArray(),
                    'retryable' => $attempt->retryable,
                    'error_code' => $attempt->error_code,
                    'started_at' => $attempt->started_at->toJSON(),
                    'completed_at' => $attempt->completed_at?->toJSON(),
                ])->all(),
        ];
    }

    /** @return array{value: int|null, key: string, label: string} */
    private function attemptType(Communication $communication): array
    {
        if ($communication->scheduled_communication_id !== null) {
            return $communication->scheduledCommunication->source->toArray();
        }

        return [
            'value' => null,
            'key' => 'campaign_schedule',
            'label' => __('Campaign schedule'),
        ];
    }
}
