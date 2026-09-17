<?php

namespace App\Services\Communication;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\OnceOffCampaignSchedule;
use App\Models\ScheduledCommunication;
use Illuminate\Support\Facades\DB;

class RecoverCommunicationWork
{
    public function __construct(private DispatchCommunicationWork $dispatch, private CompleteCampaignExecution $complete) {}

    public function handle(): void
    {
        $limit = max(1, min(100, (int) config('communication.recovery_limit')));
        $active = fn ($query) => $query->whereIn('status', [CampaignStatus::Launched, CampaignStatus::Running]);
        foreach (OnceOffCampaignSchedule::query()->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
            ->where('scheduled_at', '<=', now())->whereHas('campaign', $active)
            ->where(fn ($query) => $query->whereNull('dispatch_expires_at')->orWhere('dispatch_expires_at', '<=', now()))
            ->orderBy('scheduled_at')->limit($limit)->pluck('id') as $id) {
            $this->dispatch->schedule($id);
        }
        foreach (ScheduledCommunication::query()->where('status', CommunicationWorkStatus::Pending)
            ->where('scheduled_at', '<=', now())->whereHas('campaign', $active)
            ->where(fn ($query) => $query->whereNull('dispatch_expires_at')->orWhere('dispatch_expires_at', '<=', now()))
            ->orderBy('scheduled_at')->limit($limit)->pluck('id') as $id) {
            $this->dispatch->callback($id);
        }
        foreach (Communication::query()->where('status', CommunicationStatus::Pending)
            ->where('next_attempt_at', '<=', now())->whereHas('campaign', $active)
            ->where(fn ($query) => $query->whereNull('dispatch_expires_at')->orWhere('dispatch_expires_at', '<=', now()))
            ->orderBy('next_attempt_at')->limit($limit)->pluck('id') as $id) {
            $this->dispatch->communication($id);
        }
        // Durable completion markers also cover a crash between saving the last outcome and updating the campaign.
        foreach ([OnceOffCampaignSchedule::class, ScheduledCommunication::class, Communication::class] as $model) {
            $terminal = $model === Communication::class
                ? [CommunicationStatus::Accepted, CommunicationStatus::Sent, CommunicationStatus::Delivered, CommunicationStatus::Failed, CommunicationStatus::Cancelled]
                : [CommunicationWorkStatus::Completed, CommunicationWorkStatus::Cancelled, CommunicationWorkStatus::Expired, CommunicationWorkStatus::Failed];
            foreach ($model::query()->whereIn('status', $terminal)->whereNull('completion_checked_at')
                ->whereHas('campaign', $active)->limit($limit)->pluck('campaign_id')->unique() as $campaignId) {
                $this->complete->handle($campaignId);
            }
        }
        // Stale in-flight work is reconciled even after pause/cancel, but is never sent here.
        foreach (Communication::query()->where('status', CommunicationStatus::Processing)
            ->where('claim_expires_at', '<=', now())->orderBy('claim_expires_at')->limit($limit)->get() as $reference) {
            $this->recoverClaim($reference);
        }
    }

    private function recoverClaim(Communication $reference): void
    {
        DB::transaction(function () use ($reference): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->find($reference->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->id);
            if (! $campaign || $communication->status !== CommunicationStatus::Processing || ! $communication->claim_expires_at?->isPast()) {
                return;
            }
            $attempt = $communication->attempts()->latest('attempt_number')->first();
            $successful = $attempt?->status->successful() === true;
            // Only the explicit simulation marker proves an interrupted invocation had no external side effect.
            $safe = ! $attempt || $attempt->status === CommunicationStatus::Failed || ($attempt->request_metadata['simulated'] ?? false) === true;
            $status = $successful ? $attempt->status : ($safe ? CommunicationStatus::Pending : CommunicationStatus::Unknown);
            if ($status === CommunicationStatus::Pending && $campaign->status === CampaignStatus::Cancelled) {
                $status = CommunicationStatus::Cancelled;
            }
            if ($attempt && $attempt->status === CommunicationStatus::Processing) {
                $attempt->update([
                    'status' => $safe ? CommunicationStatus::Failed : CommunicationStatus::Unknown,
                    'retryable' => $safe, 'error_code' => $safe ? 'interrupted_simulation' : 'outcome_unknown',
                    'completed_at' => now(),
                ]);
            }
            $communication->update([
                'status' => $status, 'claim_token' => null, 'claim_expires_at' => null, 'dispatch_expires_at' => null,
                'error_code' => $status === CommunicationStatus::Unknown ? 'outcome_unknown' : $communication->error_code,
            ]);
            if ($status->finished() && $communication->scheduled_communication_id) {
                $communication->scheduledCommunication()->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
                    ->update(['status' => CommunicationWorkStatus::Completed, 'completed_at' => now()]);
            }
            DB::afterCommit(function () use ($communication): void {
                $this->dispatch->communication($communication->id);
                $this->complete->handle($communication->campaign_id);
            });
        });
    }
}
