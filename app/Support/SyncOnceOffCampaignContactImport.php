<?php

namespace App\Support;

use App\Enums\ContactImportStatus;
use App\Models\Campaign;
use App\Models\OnceOffCampaignContact;
use App\Models\OnceOffCampaignContactImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SyncOnceOffCampaignContactImport
{
    public function handle(Campaign $campaign, OnceOffCampaignContactImport $contactImport): void
    {
        DB::transaction(function () use ($campaign, $contactImport): void {
            $import = $campaign->contactImports()->whereKey($contactImport->id)->lockForUpdate()->firstOrFail();

            if ($import->status !== ContactImportStatus::Pending) {
                throw ValidationException::withMessages(['sync' => __('This import has already been synced.')]);
            }

            $now = now();
            foreach (array_chunk($import->rows ?? [], 250) as $chunk) {
                $records = array_map(fn (array $row): array => [
                    ...$row,
                    'campaign_id' => $campaign->id,
                    'public_id' => (string) Str::ulid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk);

                OnceOffCampaignContact::query()->insert($records);
            }

            $import->update(['status' => ContactImportStatus::Synced, 'synced_at' => $now, 'rows' => null]);
        });
    }
}
