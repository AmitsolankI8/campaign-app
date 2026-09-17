<?php

namespace App\Services\Communication;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Jobs\ProcessCampaignScheduleJob;
use App\Jobs\ProcessScheduledCommunicationJob;
use App\Jobs\SendCommunicationJob;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\OnceOffCampaignSchedule;
use App\Models\ScheduledCommunication;
use Illuminate\Support\Facades\DB;
use Throwable;

class DispatchCommunicationWork
{
    public function schedule(int $id): void
    {
        $this->dispatch(OnceOffCampaignSchedule::class, $id);
    }

    public function callback(int $id): void
    {
        $this->dispatch(ScheduledCommunication::class, $id);
    }

    public function communication(int $id): void
    {
        $this->dispatch(Communication::class, $id);
    }

    /** @param class-string<OnceOffCampaignSchedule>|class-string<ScheduledCommunication>|class-string<Communication> $model */
    private function dispatch(string $model, int $id): void
    {
        $reference = $model::query()->find($id);
        if (! $reference) {
            return;
        }
        DB::transaction(function () use ($model, $id, $reference): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->find($reference->campaign_id);
            if (! $campaign || ! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)) {
                return;
            }
            $work = $model::query()->lockForUpdate()->find($id);
            if (! $work || $work->dispatch_expires_at?->isFuture()) {
                return;
            }
            $allowed = $work instanceof Communication
                ? $work->status === CommunicationStatus::Pending
                : ($work instanceof ScheduledCommunication
                    ? $work->status === CommunicationWorkStatus::Pending
                    : in_array($work->status, [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing], true));
            if (! $allowed) {
                return;
            }

            $due = $work instanceof Communication ? $work->next_attempt_at : $work->scheduled_at;
            $availableAt = $due->isFuture() ? $due : now();
            $work->forceFill([
                'dispatched_at' => now(),
                'dispatch_expires_at' => $availableAt->copy()->addSeconds((int) config('communication.lease_seconds')),
            ])->save();
            $job = match (true) {
                $work instanceof Communication => new SendCommunicationJob($id, $campaign->execution_version),
                $work instanceof ScheduledCommunication => new ProcessScheduledCommunicationJob($id, $campaign->execution_version, $work->version),
                default => new ProcessCampaignScheduleJob($id, $campaign->execution_version),
            };
            DB::afterCommit(function () use ($job, $availableAt): void {
                try {
                    dispatch($job->delay($availableAt));
                } catch (Throwable $exception) {
                    // The persisted lease expires; recovery republishes this exact work.
                    report($exception);
                }
            });
        });
    }
}
