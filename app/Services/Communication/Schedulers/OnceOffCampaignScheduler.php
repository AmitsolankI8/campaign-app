<?php

namespace App\Services\Communication\Schedulers;

use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\ScheduledCommunication;
use App\Services\Communication\DispatchCommunicationWork;

class OnceOffCampaignScheduler
{
    public function __construct(private DispatchCommunicationWork $dispatch) {}

    public function schedule(OnceOffCampaign $campaign): void
    {
        $campaign->schedules()->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
            ->select('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $this->dispatch->schedule($row->id);
                }
            });
        ScheduledCommunication::query()->where('campaign_id', $campaign->id)->where('status', CommunicationWorkStatus::Pending)
            ->select('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $this->dispatch->callback($row->id);
                }
            });
        Communication::query()->where('campaign_id', $campaign->id)->where('status', CommunicationStatus::Pending)
            ->select('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $this->dispatch->communication($row->id);
                }
            });
    }
}
