<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Enums\ContactImportStatus;
use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadRowStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\OnceOffCampaignContactImport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SyncOnceOffCampaignContactImport
{
    public function __construct(private CampaignContactSyncPlan $plan) {}

    /** @param list<string>|null $rowIds */
    public function handle(OnceOffCampaign $campaign, OnceOffCampaignContactImport $contactImport, ?array $rowIds = null, ?string $fingerprint = null): void
    {
        DB::transaction(function () use ($campaign, $contactImport, $rowIds, $fingerprint): void {
            // Every sync locks the campaign first, so overlapping uploads cannot create duplicates.
            $campaign = OnceOffCampaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            $upload = $campaign->contactImports()->whereKey($contactImport->id)->lockForUpdate()->firstOrFail();
            if ($campaign->status !== CampaignStatus::Draft) {
                throw ValidationException::withMessages(['sync' => __('Contacts can only be synced while the campaign is draft.')]);
            }
            if ($upload->status === ContactImportStatus::Synced) {
                return;
            }
            $replace = $upload->mode === ContactUploadMode::Replace;
            if ($replace) {
                if ($rowIds !== null || $upload->status !== ContactImportStatus::Pending) {
                    throw ValidationException::withMessages(['sync' => __('Replace the entire list together, while the campaign is draft and the upload is pending.')]);
                }
                if (! hash_equals($this->plan->make($campaign, $upload)['fingerprint'], $fingerprint ?? '')) {
                    throw ValidationException::withMessages(['sync' => __('Campaign contacts have changed. Refresh the preview and confirm the replacement again.')]);
                }
            }

            $query = $upload->uploadedRows();
            if ($rowIds !== null) {
                if ((clone $query)->whereIn('public_id', $rowIds)->count() !== count($rowIds)) {
                    throw ValidationException::withMessages(['sync' => __('Select contacts belonging to this upload.')]);
                }
                $query->whereIn('public_id', $rowIds);
            }
            $rows = $query->whereIn('status', [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed])->orderBy('id')->get();
            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['sync' => __('There are no remaining contacts to sync.')]);
            }
            $existing = $campaign->contacts()->whereIn('normalized_number', $rows->pluck('normalized_number'))->orderBy('id')->get()->groupBy('normalized_number');
            $keepIds = [];
            foreach ($rows as $row) {
                $values = $row->contactValues();
                $validator = Validator::make($values, CampaignContactRules::rules(), CampaignContactRules::messages());
                $matches = $existing->get($row->normalized_number, new Collection);
                $error = $validator->fails() ? $validator->errors()->first() : null;
                if ($upload->mode === ContactUploadMode::Update && $matches->count() > 1) {
                    $error = 'Multiple existing contacts have this number. Use a corrected file with whole-list replacement while the campaign is draft to resolve these duplicates.';
                }
                if ($error !== null) {
                    if ($replace) {
                        throw ValidationException::withMessages(['sync' => __('Row :row: :error', ['row' => $row->row_number, 'error' => $error])]);
                    }
                    $row->update(['status' => ContactUploadRowStatus::Failed, 'error' => $error]);

                    continue;
                }

                $contact = $matches->first();
                $before = $contact?->only(['first_name', 'last_name', 'number', 'email']);
                if ($contact === null) {
                    $contact = $campaign->contacts()->create($values);
                    $existing->put($row->normalized_number, new Collection([$contact]));
                    $outcome = ContactUploadRowStatus::Added;
                } elseif ($upload->mode === ContactUploadMode::Append) {
                    $outcome = ContactUploadRowStatus::Skipped;
                } else {
                    $contact->update(array_filter($values, fn ($value): bool => $value !== null));
                    $outcome = ContactUploadRowStatus::Updated;
                }
                $keepIds[] = $contact->id;
                $row->update(['status' => $outcome, 'contact_id' => $contact->id, 'before_values' => $before, 'error' => null, 'synced_at' => now()]);
            }

            if ($replace) {
                // Soft deletion retains removed contacts and the upload responsible for removal.
                $removed = $campaign->contacts()->whereNotIn('id', $keepIds)->update([
                    'deleted_at' => now(), 'removed_by_import_id' => $upload->id, 'updated_at' => now(),
                ]);
                $upload->removed_count = $removed;
            }
            $remaining = $upload->uploadedRows()->whereIn('status', [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed])->exists();
            $processed = $upload->uploadedRows()->whereNotIn('status', [ContactUploadRowStatus::Pending, ContactUploadRowStatus::Failed])->exists();
            $upload->status = $remaining
                ? ($processed ? ContactImportStatus::PartiallySynced : ContactImportStatus::Pending)
                : ContactImportStatus::Synced;
            $upload->setAttribute('synced_at', $remaining ? null : now());
            $upload->save();
        });
    }
}
