<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\ScheduledCommunication;
use App\Services\Communication\CompleteCampaignExecution;
use App\Services\Communication\Schedulers\OnceOffCampaignScheduler;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeOnceOffCampaignStatus
{
    public function __construct(private OnceOffCampaignScheduler $scheduler, private CompleteCampaignExecution $complete) {}

    public function launch(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $this->requireStatus($campaign, [CampaignStatus::Draft], 'Only draft campaigns can be launched.');
            $errors = [];
            if ($campaign->firstSchedule()->first()?->scheduled_at === null) {
                $errors['schedule'] = __('Set a schedule before launching the campaign.');
            }
            if (! $campaign->contacts()->exists()) {
                $errors['contacts'] = __('Add and sync at least one contact before launching the campaign.');
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
            $this->transition($campaign, CampaignStatus::Launched);
            DB::afterCommit(fn () => $this->scheduler->schedule($campaign));
        });
    }

    public function stop(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $this->requireStatus($campaign, [CampaignStatus::Launched], 'Only launched campaigns can be stopped.');
            $scheduledAt = $campaign->firstSchedule()->first()?->scheduled_at;
            if ($scheduledAt === null || $scheduledAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['status' => __('The campaign can only be stopped before its first scheduled time.')]);
            }
            $this->cancelUnsent($campaign);
            // Preserve cancelled history while freeing normal attempt identities for the next launch.
            ScheduledCommunication::query()->where('campaign_id', $campaign->id)->where('replaces_attempt', true)
                ->chunkById(100, function ($callbacks): void {
                    foreach ($callbacks as $callback) {
                        $callback->update(['replaces_attempt' => false, 'idempotency_key' => 'stopped:'.$callback->public_id]);
                    }
                });
            Communication::query()->where('campaign_id', $campaign->id)->where('status', CommunicationStatus::Cancelled)
                ->chunkById(100, function ($communications): void {
                    foreach ($communications as $communication) {
                        $communication->update(['idempotency_key' => 'stopped:'.$communication->public_id]);
                    }
                });
            $campaign->schedules()->update([
                'status' => CommunicationWorkStatus::Pending, 'last_contact_id' => 0,
                'dispatched_at' => null, 'dispatch_expires_at' => null, 'started_at' => null, 'completed_at' => null,
            ]);
            $this->transition($campaign, CampaignStatus::Draft);
        });
    }

    public function pause(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $this->requireStatus($campaign, [CampaignStatus::Launched, CampaignStatus::Running], 'Only launched or running campaigns can be paused.');
            $this->transition($campaign, CampaignStatus::Paused);
        });
    }

    public function resume(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $this->requireStatus($campaign, [CampaignStatus::Paused], 'Only paused campaigns can be resumed.');
            $started = $campaign->schedules()->whereNotNull('started_at')->exists()
                || Communication::query()->where('campaign_id', $campaign->id)->whereHas('attempts')->exists();
            $this->transition($campaign, $started ? CampaignStatus::Running : CampaignStatus::Launched);
            DB::afterCommit(function () use ($campaign): void {
                $this->scheduler->schedule($campaign);
                $this->complete->handle($campaign->id);
            });
        });
    }

    public function cancel(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $this->requireStatus($campaign, [CampaignStatus::Launched, CampaignStatus::Running, CampaignStatus::Paused], 'Only launched, running, or paused campaigns can be cancelled.');
            $this->cancelUnsent($campaign);
            $this->transition($campaign, CampaignStatus::Cancelled);
        });
    }

    private function transition(OnceOffCampaign $campaign, CampaignStatus $status): void
    {
        $campaign->forceFill(['status' => $status, 'execution_version' => $campaign->execution_version + 1])->save();
        $campaign->schedules()->update(['dispatch_expires_at' => null]);
        ScheduledCommunication::query()->where('campaign_id', $campaign->id)->update(['dispatch_expires_at' => null]);
        Communication::query()->where('campaign_id', $campaign->id)->where('status', CommunicationStatus::Pending)
            ->update(['dispatch_expires_at' => null]);
    }

    private function cancelUnsent(OnceOffCampaign $campaign): void
    {
        $campaign->schedules()->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
            ->update(['status' => CommunicationWorkStatus::Cancelled, 'completed_at' => now()]);
        ScheduledCommunication::query()->where('campaign_id', $campaign->id)
            ->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
            ->whereNotIn('id', Communication::query()->where('campaign_id', $campaign->id)
                ->where('status', CommunicationStatus::Processing)->whereNotNull('scheduled_communication_id')->select('scheduled_communication_id'))
            ->update(['status' => CommunicationWorkStatus::Cancelled, 'completed_at' => now()]);
        Communication::query()->where('campaign_id', $campaign->id)->where('status', CommunicationStatus::Pending)
            ->update(['status' => CommunicationStatus::Cancelled, 'error_code' => 'campaign_cancelled']);
    }

    /** @param list<CampaignStatus> $statuses */
    private function requireStatus(OnceOffCampaign $campaign, array $statuses, string $message): void
    {
        if (! in_array($campaign->status, $statuses, true)) {
            throw ValidationException::withMessages(['status' => __($message)]);
        }
    }
}
