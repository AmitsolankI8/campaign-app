<?php

namespace App\Http\Resources\Campaign;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Campaign
 */
class CampaignRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'short_note' => $this->short_note,
            'type' => $this->campaign_type->toArray(),
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
