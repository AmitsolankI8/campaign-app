<?php

namespace App\Http\Resources\Campaign;

use App\Models\OnceOffCampaignSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnceOffCampaignSchedule */
class OnceOffCampaignScheduleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'attempt_count' => $this->attempt_count,
            'scheduled_at' => $this->scheduled_at->toJSON(),
            'channel' => $this->channel,
        ];
    }
}
