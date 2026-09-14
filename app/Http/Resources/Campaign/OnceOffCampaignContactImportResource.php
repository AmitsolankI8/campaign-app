<?php

namespace App\Http\Resources\Campaign;

use App\Enums\ContactImportStatus;
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
            'source' => $this->source->toArray(),
            'mode' => $this->mode->toArray(),
            'uploaded_by' => $this->whenLoaded('uploader', fn () => $this->uploader?->full_name),
            'processed_count' => $this->status === ContactImportStatus::Synced ? $this->contact_count : $this->processed_count,
            'history_count' => $this->uploaded_rows_count,
            'removed_count' => $this->removed_count,
            'download_url' => $this->file_path ? route('campaigns.once-off.contact-imports.download', ['campaign' => $request->route('campaign'), 'contactImport' => $this->public_id]) : null,
            'created_at' => $this->created_at?->toJSON(),
            'synced_at' => $this->synced_at?->toJSON(),
        ];
    }
}
