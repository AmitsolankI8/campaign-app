<?php

namespace App\Services\Communication\Providers;

use App\Contracts\Communication\EmailProvider;
use App\Contracts\Communication\SmsProvider;
use App\Contracts\Communication\VoiceProvider;
use App\Enums\CommunicationStatus;
use App\Services\Communication\Data\EmailMessage;
use App\Services\Communication\Data\ProviderContext;
use App\Services\Communication\Data\ProviderResponse;
use App\Services\Communication\Data\SmsMessage;
use App\Services\Communication\Data\VoiceMessage;
use Illuminate\Support\Facades\App;
use LogicException;

class SimulatedProvider implements EmailProvider, SmsProvider, VoiceProvider
{
    public function __construct(private ProviderContext $context) {}

    public function send(SmsMessage|EmailMessage $message): ProviderResponse
    {
        return $this->respond($message->idempotencyKey);
    }

    public function call(VoiceMessage $message): ProviderResponse
    {
        return $this->respond($message->idempotencyKey);
    }

    private function respond(string $key): ProviderResponse
    {
        if (! config('communication.simulation.enabled') || ! App::environment(['local', 'testing'])) {
            throw new LogicException('Communication simulation is disabled outside local/testing.');
        }

        $metadata = ['simulated' => true];

        return match ($this->context->outcome) {
            'success' => new ProviderResponse(
                CommunicationStatus::Sent,
                'sim_'.hash('sha256', $this->context->accountPublicId.':'.$key),
                metadata: $metadata,
            ),
            'failed' => new ProviderResponse(CommunicationStatus::Failed, errorCode: 'simulated_rejection', errorMessage: 'Simulated provider rejection.', metadata: $metadata),
            'retryable' => new ProviderResponse(CommunicationStatus::Failed, errorCode: 'simulated_temporary_failure', errorMessage: 'Simulated temporary failure.', retryable: true, metadata: $metadata),
            default => new ProviderResponse(CommunicationStatus::Unknown, errorCode: 'outcome_unknown', metadata: $metadata),
        };
    }
}
