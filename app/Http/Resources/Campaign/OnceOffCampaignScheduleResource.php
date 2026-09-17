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
            'attempt_number' => $this->attempt_number,
            'scheduled_at' => $this->scheduled_at->toJSON(),
            'channel' => $this->channel->code,
            'status' => $this->status->toArray(),
            'timezone' => $this->timezone,
        ];
    }
}
