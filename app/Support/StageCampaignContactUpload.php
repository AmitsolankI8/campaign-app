<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Enums\ContactUploadMode;
use App\Enums\ContactUploadSource;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\OnceOffCampaignContactImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class StageCampaignContactUpload
{
    /** @param list<array<string, mixed>> $rows */
    public function handle(OnceOffCampaign $campaign, User $user, ContactUploadMode $mode, array $rows, ?UploadedFile $file = null): OnceOffCampaignContactImport
    {
        $path = null;
        try {
            return DB::transaction(function () use ($campaign, $user, $mode, $rows, $file, &$path): OnceOffCampaignContactImport {
                $lockedCampaign = OnceOffCampaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
                if ($lockedCampaign->status !== CampaignStatus::Draft) {
                    throw ValidationException::withMessages(['mode' => __('Contacts can only be uploaded while the campaign is draft.')]);
                }
                if ($mode === ContactUploadMode::Replace && $file === null) {
                    throw ValidationException::withMessages(['mode' => __('Whole-list replacement requires a file upload and a draft campaign.')]);
                }

                if ($file !== null) {
                    $path = $file->store('campaign-contact-uploads', 'local');
                    if ($path === false) {
                        throw new RuntimeException('Unable to store the contact upload.');
                    }
                }

                $upload = $lockedCampaign->contactImports()->create([
                    'file_name' => $file ? Str::limit($file->getClientOriginalName(), 255, '') : __('Manual contact'),
                    'file_path' => $path,
                    'source' => $file ? ContactUploadSource::File : ContactUploadSource::Manual,
                    'mode' => $mode, 'uploaded_by' => $user->id, 'contact_count' => count($rows),
                ]);
                $now = now();
                foreach (array_chunk($rows, 250) as $chunk) {
                    $upload->uploadedRows()->insert(array_map(fn (array $row): array => [
                        ...$row, 'contact_import_id' => $upload->id, 'public_id' => (string) Str::ulid(),
                        'created_at' => $now, 'updated_at' => $now,
                    ], $chunk));
                }

                return $upload;
            });
        } catch (Throwable $exception) {
            if (is_string($path)) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }
}
