<?php

namespace App\Services\Communication\Data;

final readonly class CommunicationRequest
{
    public function __construct(
        public int $communicationId,
        public int $executionVersion,
        public SmsMessage|VoiceMessage|EmailMessage $message,
    ) {}
}
