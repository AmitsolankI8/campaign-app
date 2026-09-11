<?php

namespace App\Http\Resources\Campaign;

use App\Models\OnceOffCampaignContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnceOffCampaignContact */
class OnceOffCampaignContactResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'number' => $this->number,
            'email' => $this->email,
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
