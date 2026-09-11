<?php

namespace App\Http\Resources\Campaign;

use App\Models\OnceOffCampaignContactImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnceOffCampaignContactImport */
class OnceOffCampaignContactImportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'file_name' => $this->file_name,
            'contact_count' => $this->contact_count,
            'status' => $this->status->toArray(),
            'created_at' => $this->created_at?->toJSON(),
            'synced_at' => $this->synced_at?->toJSON(),
        ];
    }
}
