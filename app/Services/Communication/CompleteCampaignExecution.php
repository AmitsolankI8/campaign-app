<?php

namespace App\Services\Communication;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\ScheduledCommunication;
use Illuminate\Support\Facades\DB;

class CompleteCampaignExecution
{
    public function handle(int $campaignId): void
    {
        DB::transaction(function () use ($campaignId): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->find($campaignId);
            if (! $campaign || ! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)) {
                return;
            }
            $unfinished = [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing];
            $campaign->schedules()->whereNotIn('status', $unfinished)->whereNull('completion_checked_at')->update(['completion_checked_at' => now()]);
            ScheduledCommunication::query()->where('campaign_id', $campaignId)->whereNotIn('status', $unfinished)
                ->whereNull('completion_checked_at')->update(['completion_checked_at' => now()]);
            Communication::query()->where('campaign_id', $campaignId)
                ->whereNotIn('status', [CommunicationStatus::Pending, CommunicationStatus::Processing, CommunicationStatus::Unknown])
                ->whereNull('completion_checked_at')->update(['completion_checked_at' => now()]);
            if ($campaign->schedules()->whereIn('status', $unfinished)->exists()
                || ScheduledCommunication::query()->where('campaign_id', $campaignId)->whereIn('status', $unfinished)->exists()
                || Communication::query()->where('campaign_id', $campaignId)
                    ->whereIn('status', [CommunicationStatus::Pending, CommunicationStatus::Processing, CommunicationStatus::Unknown])->exists()) {
                return;
            }

            $campaign->update(['status' => CampaignStatus::Completed]);
        });
    }
}
