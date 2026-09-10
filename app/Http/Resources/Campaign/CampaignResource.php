<?php

namespace App\Http\Resources\Campaign;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Campaign
 */
class CampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'type' => $this->campaign_type->toArray(),
            'status' => $this->status->toArray(),
            'short_note' => $this->short_note,
            'show_url' => route($this->campaign_type->showRouteName(), $this->resource),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
