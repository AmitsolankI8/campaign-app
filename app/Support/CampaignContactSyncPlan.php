<?php

namespace App\Support;

use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadRowStatus;
use App\Models\Campaign;
use App\Models\OnceOffCampaignContactImport;
use Illuminate\Support\Facades\DB;

class CampaignContactSyncPlan
{
    /** @return array{add: int, update: int, skip: int, remove: int, fingerprint: string} */
    public function make(Campaign $campaign, OnceOffCampaignContactImport $upload): array
    {
        // Keep replacement counts and their confirmation fingerprint from the same campaign state.
        return DB::transaction(function () use ($campaign, $upload): array {
            $lockedCampaign = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            $lockedUpload = $lockedCampaign->contactImports()->whereKey($upload->id)->firstOrFail();

            return $this->build($lockedCampaign, $lockedUpload);
        });
    }

    /** @return array{add: int, update: int, skip: int, remove: int, fingerprint: string} */
    private function build(Campaign $campaign, OnceOffCampaignContactImport $upload): array
    {
        $numbers = $upload->uploadedRows()->whereIn('status', [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed])->pluck('normalized_number')->all();
        $existing = $campaign->onceOffContacts()->whereIn('normalized_number', $numbers)->pluck('normalized_number')->unique()->all();
        $add = count(array_diff($numbers, $existing));
        $matched = count($numbers) - $add;
        $remove = $upload->mode === ContactUploadMode::Replace ? $campaign->onceOffContacts()->count() - count($existing) : 0;
        $hash = hash_init('sha256');
        hash_update($hash, $campaign->public_id.'|'.$campaign->status->value.'|'.$upload->public_id.'|'.$upload->mode->value.'|'.$upload->status->value);
        if ($upload->mode === ContactUploadMode::Replace) {
            foreach ($campaign->onceOffContacts()->orderBy('id')->cursor() as $contact) {
                hash_update($hash, json_encode([$contact->id, $contact->first_name, $contact->last_name, $contact->number, $contact->email], JSON_THROW_ON_ERROR));
            }
        }
        hash_update($hash, json_encode($numbers, JSON_THROW_ON_ERROR));

        return [
            'add' => $add,
            'update' => $upload->mode === ContactUploadMode::Append ? 0 : $matched,
            'skip' => $upload->mode === ContactUploadMode::Append ? $matched : 0,
            'remove' => $remove,
            'fingerprint' => hash_hmac('sha256', hash_final($hash), (string) config('app.key')),
        ];
    }
}
