<?php

namespace App\Http\Resources\Campaign;

use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadRowStatus;
use App\Models\OnceOffCampaignContactUploadRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OnceOffCampaignContactUploadRow */
class ContactUploadRowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $pending = in_array($this->status, [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed], true);
        $action = $this->existing_count > 0 ? ($this->contactImport->mode === ContactUploadMode::Append ? 'skip' : 'update') : 'add';

        return [
            'id' => $this->public_id, 'row_number' => $this->row_number, ...$this->contactValues(),
            'status' => $this->status->toArray(), 'planned_action' => $pending ? $action : null,
            'error' => $this->error, 'before_values' => $this->before_values,
            'synced_at' => $this->synced_at?->toJSON(),
        ];
    }
}
