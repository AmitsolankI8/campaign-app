<?php

namespace App\Services\Communication;

use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Campaigns\OnceOffCampaign;
use App\Models\Communication;
use App\Models\CommunicationAttempt;
use App\Services\Communication\Data\ProviderEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApplyProviderEvent
{
    public function __construct(private CompleteCampaignExecution $complete) {}

    public function handle(ProviderEvent $event): void
    {
        if (! in_array($event->status, [CommunicationStatus::Accepted, CommunicationStatus::Sent, CommunicationStatus::Delivered, CommunicationStatus::Failed], true)) {
            throw new InvalidArgumentException('Unsupported provider event status.');
        }
        $occurredAt = Carbon::parse($event->occurredAt)->utc();
        $reference = CommunicationAttempt::query()->with('communication')
            ->where('provider_account_id', $event->providerAccountId)->where('provider_message_id', $event->providerMessageId)->first();
        if (! $reference) {
            return;
        }
        DB::transaction(function () use ($reference, $event, $occurredAt): void {
            OnceOffCampaign::query()->lockForUpdate()->findOrFail($reference->communication->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->communication_id);
            $attempt = CommunicationAttempt::query()->lockForUpdate()->findOrFail($reference->id);
            if ($attempt->status === CommunicationStatus::Delivered
                || $attempt->provider_event_at?->greaterThanOrEqualTo($occurredAt)
                || ($attempt->status === CommunicationStatus::Sent && $event->status === CommunicationStatus::Accepted)) {
                return;
            }
            $attempt->update(['status' => $event->status, 'provider_event_at' => $occurredAt, 'completed_at' => now()]);
            $otherSuccess = $communication->attempts()->whereKeyNot($attempt->id)
                ->whereIn('status', [CommunicationStatus::Accepted, CommunicationStatus::Sent, CommunicationStatus::Delivered])->exists();
            if ($communication->status !== CommunicationStatus::Delivered && ! $otherSuccess
                && ($event->status->successful() || (int) $communication->attempts()->max('attempt_number') === $attempt->attempt_number)) {
                $communication->status = $event->status;
                if (in_array($event->status, [CommunicationStatus::Sent, CommunicationStatus::Delivered], true)) {
                    $communication->sent_at ??= $occurredAt;
                }
                if ($event->status === CommunicationStatus::Delivered) {
                    $communication->delivered_at = $occurredAt;
                }
                if ($event->status === CommunicationStatus::Failed) {
                    $communication->failed_at = $occurredAt;
                }
                $communication->save();
                if ($communication->scheduled_communication_id) {
                    $communication->scheduledCommunication()->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
                        ->update(['status' => CommunicationWorkStatus::Completed, 'completed_at' => now()]);
                }
                DB::afterCommit(fn () => $this->complete->handle($communication->campaign_id));
            }
        });
    }
}
