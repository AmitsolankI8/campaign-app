<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeOnceOffCampaignStatus
{
    public function launch(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

            if ($campaign->status !== CampaignStatus::Draft) {
                throw ValidationException::withMessages(['status' => __('Only draft campaigns can be launched.')]);
            }

            $errors = [];
            if ($campaign->firstOnceOffSchedule()->first()?->scheduled_at === null) {
                $errors['schedule'] = __('Set a schedule before launching the campaign.');
            }
            if (! $campaign->onceOffContacts()->exists()) {
                $errors['contacts'] = __('Add and sync at least one contact before launching the campaign.');
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $campaign->update(['status' => CampaignStatus::Launched]);
        });
    }

    public function stop(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

            if ($campaign->status !== CampaignStatus::Launched) {
                throw ValidationException::withMessages(['status' => __('Only launched campaigns can be stopped.')]);
            }

            $scheduledAt = $campaign->firstOnceOffSchedule()->first()?->scheduled_at;
            if ($scheduledAt === null || $scheduledAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['status' => __('The campaign can only be stopped before its first scheduled time.')]);
            }

            $campaign->update(['status' => CampaignStatus::Draft]);
        });
    }
}
