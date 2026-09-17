<?php

namespace App\Services\Communication;

use App\Contracts\Communication\EmailProvider;
use App\Contracts\Communication\SmsProvider;
use App\Contracts\Communication\VoiceProvider;
use App\Enums\CampaignStatus;
use App\Enums\CommunicationStatus;
use App\Enums\CommunicationWorkStatus;
use App\Models\Communication;
use App\Models\CommunicationAttempt;
use App\Services\Communication\Data\CommunicationRequest;
use App\Services\Communication\Data\EmailMessage;
use App\Services\Communication\Data\ProviderResponse;
use App\Services\Communication\Data\ResolvedProvider;
use App\Services\Communication\Data\SmsMessage;
use App\Services\Communication\Data\VoiceMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CommunicationManager
{
    public function __construct(
        private ProviderResolver $providers,
        private CommunicationEligibility $eligibility,
        private DispatchCommunicationWork $dispatch,
        private CompleteCampaignExecution $complete,
        private CommunicationExecutionContext $context,
    ) {}

    public function reject(int $id, int $version, string $errorCode): void
    {
        $reference = Communication::query()->find($id);
        if (! $reference) {
            return;
        }
        DB::transaction(function () use ($reference, $version, $errorCode): void {
            $campaign = $this->context->lockCampaign($reference->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->id);
            if ($campaign && $campaign->execution_version === $version
                && in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)
                && $communication->status === CommunicationStatus::Pending && ! $communication->next_attempt_at->isFuture()) {
                $this->finish($communication, new ProviderResponse(CommunicationStatus::Failed, errorCode: $errorCode));
            }
        });
    }

    public function send(CommunicationRequest $request): ProviderResponse
    {
        $reference = Communication::query()->findOrFail($request->communicationId);
        $token = (string) Str::uuid();
        $claimed = DB::transaction(function () use ($reference, $request, $token): bool {
            $campaign = $this->context->lockCampaign($reference->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->id);
            if (! $campaign || $campaign->execution_version !== $request->executionVersion
                || ! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)
                || $communication->status !== CommunicationStatus::Pending || $communication->next_attempt_at->isFuture()) {
                return false;
            }
            $successful = $communication->attempts()->whereIn('status', [
                CommunicationStatus::Accepted, CommunicationStatus::Sent, CommunicationStatus::Delivered,
            ])->first();
            if ($successful) {
                $this->finish($communication, new ProviderResponse($successful->status, $successful->provider_message_id));

                return false;
            }
            $error = $this->eligibility->error($communication);
            if ($error !== null) {
                $this->finish($communication, new ProviderResponse(CommunicationStatus::Cancelled, errorCode: $error));

                return false;
            }
            $messageMatches = match ($communication->channel->code) {
                'sms' => $request->message instanceof SmsMessage && $request->message->to === $communication->contact->number,
                'voice' => $request->message instanceof VoiceMessage && $request->message->to === $communication->contact->number,
                'email' => $request->message instanceof EmailMessage && $request->message->to === $communication->contact->email,
                default => false,
            };
            $hasContent = $request->message instanceof VoiceMessage
                ? filled($request->message->script)
                : filled($request->message->body);
            if ($request->message instanceof EmailMessage && blank($request->message->subject)) {
                $hasContent = false;
            }
            if (! $messageMatches || ! $hasContent || $request->message->idempotencyKey !== $communication->idempotency_key) {
                $this->finish($communication, new ProviderResponse(CommunicationStatus::Failed, errorCode: 'invalid_message'));

                return false;
            }
            $campaign->update(['status' => CampaignStatus::Running]);
            $communication->forceFill([
                'status' => CommunicationStatus::Processing, 'claim_token' => $token,
                'claim_expires_at' => now()->addSeconds((int) config('communication.lease_seconds')),
            ])->save();

            return true;
        });

        if (! $claimed) {
            $reference->refresh();

            return new ProviderResponse($reference->status, errorCode: $reference->error_code);
        }

        foreach ($this->providers->resolve($reference->channel_id) as $provider) {
            $attempt = $this->startAttempt($reference, $request->executionVersion, $token, $provider);
            if ($attempt === null) {
                if ($reference->fresh()?->status !== CommunicationStatus::Processing) {
                    return new ProviderResponse($reference->fresh()->status);
                }

                continue;
            }
            try {
                $response = match (true) {
                    $request->message instanceof SmsMessage && $provider->adapter instanceof SmsProvider => $provider->adapter->send($request->message),
                    $request->message instanceof VoiceMessage && $provider->adapter instanceof VoiceProvider => $provider->adapter->call($request->message),
                    $request->message instanceof EmailMessage && $provider->adapter instanceof EmailProvider => $provider->adapter->send($request->message),
                    default => new ProviderResponse(CommunicationStatus::Failed, errorCode: 'unsupported_adapter'),
                };
            } catch (Throwable) {
                // A transport exception cannot prove that the provider did not accept the request.
                $response = new ProviderResponse(CommunicationStatus::Unknown, errorCode: 'outcome_unknown');
            }

            $this->recordResponse($reference, $attempt, $token, $response);
            if ($response->successful() || $response->retryable || $response->status === CommunicationStatus::Unknown) {
                return $response;
            }
        }

        $response = new ProviderResponse(CommunicationStatus::Failed, errorCode: 'providers_exhausted');
        DB::transaction(function () use ($reference, $token, $response): void {
            $this->context->lockCampaign($reference->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->id);
            if ($communication->claim_token === $token && $communication->status === CommunicationStatus::Processing) {
                $this->finish($communication, $response);
            }
        });

        return $response;
    }

    private function startAttempt(Communication $reference, int $version, string $token, ResolvedProvider $provider): ?CommunicationAttempt
    {
        return DB::transaction(function () use ($reference, $version, $token, $provider): ?CommunicationAttempt {
            $campaign = $this->context->lockCampaign($reference->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->id);
            if ($communication->claim_token !== $token || $communication->status !== CommunicationStatus::Processing) {
                return null;
            }
            if (! $campaign || $campaign->execution_version !== $version
                || ! in_array($campaign->status, [CampaignStatus::Launched, CampaignStatus::Running], true)) {
                $status = $campaign?->status === CampaignStatus::Cancelled ? CommunicationStatus::Cancelled : CommunicationStatus::Pending;
                $this->finish($communication, new ProviderResponse($status));

                return null;
            }
            if ($communication->attempts()->where('provider_account_id', $provider->accountId)
                ->where('status', CommunicationStatus::Failed)->where('retryable', false)->exists()) {
                return null;
            }

            return $communication->attempts()->create([
                'provider_account_id' => $provider->accountId,
                'attempt_number' => ((int) $communication->attempts()->max('attempt_number')) + 1,
                'started_at' => now(),
                'request_metadata' => ['simulated' => $provider->simulated, 'correlation_id' => $communication->correlation_id],
            ]);
        });
    }

    private function recordResponse(Communication $reference, CommunicationAttempt $attempt, string $token, ProviderResponse $response): void
    {
        DB::transaction(function () use ($reference, $attempt, $token, $response): void {
            $campaign = $this->context->lockCampaign($reference->campaign_id);
            $communication = Communication::query()->lockForUpdate()->findOrFail($reference->id);
            if ($communication->claim_token !== $token || $communication->status !== CommunicationStatus::Processing) {
                return;
            }
            $attempt->update([
                'status' => $response->status, 'provider_message_id' => $response->providerMessageId,
                'error_code' => $response->errorCode, 'error_message' => $response->errorMessage,
                'retryable' => $response->retryable, 'response_metadata' => $response->metadata,
                'completed_at' => now(),
            ]);
            if ($response->retryable) {
                $retry = $communication->retry_count + 1;
                $communication->retry_count = $retry;
                if ($campaign?->status === CampaignStatus::Cancelled || $retry > (int) config('communication.max_retries')) {
                    $this->finish($communication, new ProviderResponse(
                        $campaign?->status === CampaignStatus::Cancelled ? CommunicationStatus::Cancelled : CommunicationStatus::Failed,
                        errorCode: 'retry_exhausted',
                    ));

                    return;
                }
                $backoff = config('communication.backoff');
                $communication->next_attempt_at = now()->addSeconds($backoff[min($retry - 1, count($backoff) - 1)]);
                $this->finish($communication, new ProviderResponse(CommunicationStatus::Pending, errorCode: $response->errorCode));
                DB::afterCommit(fn () => $this->dispatch->communication($communication->id));
            } elseif ($response->successful() || $response->status === CommunicationStatus::Unknown) {
                $this->finish($communication, $response);
            }
        });
    }

    private function finish(Communication $communication, ProviderResponse $response): void
    {
        $communication->forceFill([
            'status' => $response->status, 'error_code' => $response->errorCode,
            'claim_token' => null, 'claim_expires_at' => null, 'dispatch_expires_at' => null,
        ]);
        if (in_array($response->status, [CommunicationStatus::Sent, CommunicationStatus::Delivered], true)) {
            $communication->sent_at ??= now();
        }
        if ($response->status === CommunicationStatus::Delivered) {
            $communication->delivered_at ??= now();
        }
        if ($response->status === CommunicationStatus::Failed) {
            $communication->failed_at = now();
        }
        $communication->save();
        if ($response->status->finished() && $communication->scheduled_communication_id !== null) {
            $communication->scheduledCommunication()->whereIn('status', [CommunicationWorkStatus::Pending, CommunicationWorkStatus::Processing])
                ->update(['status' => CommunicationWorkStatus::Completed, 'completed_at' => now()]);
        }
        DB::afterCommit(fn () => $this->complete->handle($communication->campaign_id));
    }
}
