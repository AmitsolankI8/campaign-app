<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Models\Campaigns\OnceOffCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeOnceOffCampaignStatus
{
    public function launch(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if ($campaign->status !== CampaignStatus::Draft) {
                throw ValidationException::withMessages(['status' => __('Only draft campaigns can be launched.')]);
            }

            $errors = [];
            if ($campaign->firstSchedule()->first()?->scheduled_at === null) {
                $errors['schedule'] = __('Set a schedule before launching the campaign.');
            }
            if (! $campaign->contacts()->exists()) {
                $errors['contacts'] = __('Add and sync at least one contact before launching the campaign.');
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $campaign->update(['status' => CampaignStatus::Launched]);
        });
    }

    public function stop(OnceOffCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if ($campaign->status !== CampaignStatus::Launched) {
                throw ValidationException::withMessages(['status' => __('Only launched campaigns can be stopped.')]);
            }

            $scheduledAt = $campaign->firstSchedule()->first()?->scheduled_at;
            if ($scheduledAt === null || $scheduledAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['status' => __('The campaign can only be stopped before its first scheduled time.')]);
            }

            $campaign->update(['status' => CampaignStatus::Draft]);
        });
    }
}
