<?php

namespace App\Services\Communication;

use App\Enums\CampaignStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\OnceOffCampaignSchedule;
use App\Models\ScheduledCommunication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateCampaignCommunications
{
    public function __construct(private DispatchCommunicationWork $dispatch, private CompleteCampaignExecution $complete) {}

    public static function key(string $schedulePublicId, string $contactPublicId): string
    {
        return 'schedule:'.$schedulePublicId.':contact:'.$contactPublicId;
    }

    public function handle(int $scheduleId, int $version): void
    {
        $reference = OnceOffCampaignSchedule::query()->find($scheduleId);
        if (! $reference) {
            return;
        }
        DB::transaction(function () use ($reference, $version): void {
            $campaign = OnceOffCampaign::query()->lockForUpdate()->find($reference->campaign_id);
            if (! $campaign || $campaign->execution_version !== $version
                || ! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)) {
                return;
            }
            $schedule = $campaign->schedules()->lockForUpdate()->find($reference->id);
            if (! $schedule || $schedule->scheduled_at->isFuture()
                || ! in_array($schedule->status, [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing], true)) {
                return;
            }
            $campaign->update(['status' => CampaignStatus::Running]);
            $size = max(1, (int) config('communication.chunk_size'));
            $contacts = $campaign->contacts()->where('id', '>', $schedule->last_contact_id)->orderBy('id')->limit($size)->get();
            $overrides = ScheduledCommunication::query()->where('campaign_schedule_id', $schedule->id)
                ->where('replaces_attempt', true)->whereIn('contact_id', $contacts->modelKeys())->pluck('contact_id')->all();
            foreach ($contacts as $contact) {
                if (in_array($contact->id, $overrides, true)) {
                    continue;
                }
                $communication = Communication::query()->firstOrCreate(
                    ['idempotency_key' => self::key($schedule->public_id, $contact->public_id)],
                    [
                        'campaign_id' => $campaign->id, 'campaign_schedule_id' => $schedule->id,
                        'contact_id' => $contact->id, 'channel_id' => $schedule->channel_id,
                        'correlation_id' => (string) Str::ulid(), 'scheduled_at' => $schedule->scheduled_at,
                        'next_attempt_at' => $schedule->scheduled_at,
                    ],
                );
                DB::afterCommit(fn () => $this->dispatch->communication($communication->id));
            }
            $finished = $contacts->count() < $size;
            $schedule->forceFill([
                'status' => $finished ? CommunicationWorkStatus::Completed : CommunicationWorkStatus::Processing,
                'last_contact_id' => $contacts->last()->id ?? $schedule->last_contact_id,
                'started_at' => $schedule->started_at ?? now(),
                'completed_at' => $finished ? now() : null,
                'dispatch_expires_at' => null,
            ])->save();
            DB::afterCommit(fn () => $finished
                ? $this->complete->handle($campaign->id)
                : $this->dispatch->schedule($schedule->id));
        });
    }
}
