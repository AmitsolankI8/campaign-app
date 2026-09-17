<?php

namespace App\Services\Communication;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\ScheduledCommunication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExecuteScheduledCommunication
{
    public function __construct(private DispatchCommunicationWork $dispatch, private CompleteCampaignExecution $complete) {}

    public function handle(int $id, int $executionVersion, int $version): void
    {
        $reference = ScheduledCommunication::query()->find($id);
        if (! $reference) {
            return;
        }
        DB::transaction(function () use ($reference, $executionVersion, $version): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->find($reference->campaign_id);
            if (! $campaign || $campaign->execution_version !== $executionVersion
                || ! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)) {
                return;
            }
            $callback = ScheduledCommunication::query()->lockForUpdate()->findOrFail($reference->id);
            if ($callback->version !== $version || $callback->status !== CommunicationWorkStatus::Pending || $callback->scheduled_at->isFuture()) {
                return;
            }
            if ($callback->expires_at?->lessThanOrEqualTo(now()) || ! $campaign->contacts()->whereKey($callback->contact_id)->exists()) {
                $callback->update([
                    'status' => $callback->expires_at?->lessThanOrEqualTo(now()) ? CommunicationWorkStatus::Expired : CommunicationWorkStatus::Cancelled,
                    'completed_at' => now(),
                ]);
                Communication::query()->where('scheduled_communication_id', $callback->id)->where('status', CommunicationStatus::Pending)
                    ->update(['status' => CommunicationStatus::Cancelled, 'error_code' => 'callback_unavailable']);
                DB::afterCommit(fn () => $this->complete->handle($campaign->id));

                return;
            }
            $communication = Communication::query()->firstOrCreate(['idempotency_key' => $callback->idempotency_key], [
                'campaign_id' => $campaign->id, 'campaign_schedule_id' => $callback->campaign_schedule_id,
                'scheduled_communication_id' => $callback->id, 'contact_id' => $callback->contact_id,
                'channel_id' => $callback->channel_id, 'correlation_id' => (string) Str::ulid(),
                'scheduled_at' => $callback->scheduled_at, 'next_attempt_at' => $callback->scheduled_at,
            ]);
            $callback->update(['status' => CommunicationWorkStatus::Processing, 'dispatch_expires_at' => null]);
            DB::afterCommit(fn () => $this->dispatch->communication($communication->id));
        });
    }
}
