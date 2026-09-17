<?php

namespace App\Services\Communication;

use App\Enums\CampaignType;
use App\Models\Campaign;
use App\Models\Campaigns\OnceOffCampaign;

class CommunicationExecutionContext
{
    public function lockCampaign(int $id): ?Campaign
    {
        // Resolve the matching scoped model before any type-specific state changes.
        $type = Campaign::query()->whereKey($id)->value('campaign_type');

        return match ($type) {
            CampaignType::OnceOff, CampaignType::OnceOff->value => OnceOffCampaign::query()->lockForUpdate()->find($id),
            default => null,
        };
    }
}
