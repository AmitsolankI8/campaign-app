<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\CommunicationChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveOnceOffCampaignSchedules
{
    /** @param list<array{id?: string|null, scheduled_at: string, channel: string}> $attempts */
    public function handle(OnceOffCampaign $campaign, array $attempts): void
    {
        DB::transaction(function () use ($campaign, $attempts): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->findOrFail($campaign->id);

            if ($campaign->status !== CampaignStatus::Draft) {
                throw ValidationException::withMessages(['schedules' => __('The schedule can only be changed while the campaign is draft.')]);
            }

            if ($attempts === []) {
                throw ValidationException::withMessages(['schedules' => __('Keep at least one schedule attempt.')]);
            }

            $existing = $campaign->schedules()->get()->keyBy('public_id');
            foreach ($attempts as $index => $attempt) {
                if (isset($attempt['id']) && ! $existing->has($attempt['id'])) {
                    throw ValidationException::withMessages(["schedules.{$index}.id" => __('This attempt is no longer available. Reload the schedule.')]);
                }
            }

            $retainedIds = array_filter(array_column($attempts, 'id'));
            $campaign->schedules()->whereNotIn('public_id', $retainedIds)->delete();

            // Free occupied numbers so remaining attempts can be renumbered after removals.
            $offset = (int) $existing->max('attempt_number') + count($attempts);
            $campaign->schedules()->increment('attempt_number', $offset);

            foreach ($attempts as $index => $attempt) {
                $schedule = isset($attempt['id'])
                    ? $existing->get($attempt['id'])
                    : $campaign->schedules()->make();

                if ($schedule->exists) {
                    $schedule->attempt_number += $offset;
                    $schedule->syncOriginalAttribute('attempt_number');
                }

                $schedule->fill([
                    'attempt_number' => $index + 1,
                    'scheduled_at' => Carbon::parse($attempt['scheduled_at'])->utc(),
                    'channel_id' => CommunicationChannel::query()->where('code', $attempt['channel'])->where('is_active', true)->firstOrFail()->id,
                    'timezone' => UserPreferences::forUser(auth()->user())['timezone']['identifier'] ?? 'UTC',
                ]);
                $schedule->save();
            }
        });
    }
}
