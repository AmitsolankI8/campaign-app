<?php

namespace App\Support;

use App\Enums\CampaignStatus;
use App\Enums\CampaignType;
use App\Models\Campaign;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveOnceOffCampaignSchedules
{
    /** @param list<array{id?: string|null, scheduled_at: string, channel: string}> $attempts */
    public function handle(Campaign $campaign, array $attempts): void
    {
        DB::transaction(function () use ($campaign, $attempts): void {
            $campaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            abort_unless($campaign->campaign_type === CampaignType::OnceOff, 404);

            if ($campaign->status !== CampaignStatus::Draft) {
                throw ValidationException::withMessages(['schedules' => __('The schedule can only be changed while the campaign is draft.')]);
            }

            if ($attempts === []) {
                throw ValidationException::withMessages(['schedules' => __('Keep at least one schedule attempt.')]);
            }

            $existing = $campaign->onceOffSchedules()->get()->keyBy('public_id');
            foreach ($attempts as $index => $attempt) {
                if (isset($attempt['id']) && ! $existing->has($attempt['id'])) {
                    throw ValidationException::withMessages(["schedules.{$index}.id" => __('This attempt is no longer available. Reload the schedule.')]);
                }
            }

            $retainedIds = array_filter(array_column($attempts, 'id'));
            $campaign->onceOffSchedules()->whereNotIn('public_id', $retainedIds)->delete();

            // Free occupied numbers so remaining attempts can be renumbered after removals.
            $offset = (int) $existing->max('attempt_count') + count($attempts);
            $campaign->onceOffSchedules()->increment('attempt_count', $offset);

            foreach ($attempts as $index => $attempt) {
                $schedule = isset($attempt['id'])
                    ? $existing->get($attempt['id'])
                    : $campaign->onceOffSchedules()->make();

                if ($schedule->exists) {
                    $schedule->attempt_count += $offset;
                    $schedule->syncOriginalAttribute('attempt_count');
                }

                $schedule->fill([
                    'attempt_count' => $index + 1,
                    'scheduled_at' => Carbon::parse($attempt['scheduled_at'])->utc(),
                    'channel' => $attempt['channel'],
                ]);
                $schedule->save();
            }
        });
    }
}
