<?php

namespace App\Services\Communication;

use App\Enums\CommunicationWorkStatus;
use App\Models\Communication;
use App\Models\ScheduledCommunication;

class CommunicationEligibility
{
    // Called while holding the campaign and communication locks.
    public function error(Communication $communication): ?string
    {
        $communication->load(['contact', 'channel', 'scheduledCommunication', 'schedule']);
        if (! $communication->contact || $communication->contact->campaign_id !== $communication->campaign_id) {
            return 'contact_unavailable';
        }
        if (! $communication->channel->is_active) {
            return 'channel_inactive';
        }
        if ($communication->campaign_schedule_id !== null
            && $communication->schedule?->campaign_id !== $communication->campaign_id) {
            return 'schedule_unavailable';
        }
        $callback = $communication->scheduledCommunication;
        if ($callback) {
            if ($callback->campaign_id !== $communication->campaign_id || $callback->contact_id !== $communication->contact_id) {
                return 'callback_unavailable';
            }
            if ($callback->expires_at?->lessThanOrEqualTo(now())) {
                $callback->update(['status' => CommunicationWorkStatus::Expired, 'completed_at' => now()]);

                return 'callback_expired';
            }
            if (in_array($callback->status, [CommunicationWorkStatus::Cancelled, CommunicationWorkStatus::Expired], true)) {
                return 'callback_unavailable';
            }
        } elseif ($communication->campaign_schedule_id !== null
            && ScheduledCommunication::query()->where('campaign_schedule_id', $communication->campaign_schedule_id)
                ->where('contact_id', $communication->contact_id)->where('replaces_attempt', true)->exists()) {
            return 'attempt_overridden';
        }
        $destination = $communication->channel->code === 'email' ? $communication->contact->email : $communication->contact->number;
        if ($communication->channel->code === 'email') {
            return filter_var($destination, FILTER_VALIDATE_EMAIL) ? null : 'invalid_destination';
        }

        return preg_match('/^\\+[1-9]\\d{6,14}$/', $destination ?? '') === 1 ? null : 'invalid_destination';
    }
}
