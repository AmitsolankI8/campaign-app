<?php

namespace App\Services\Communication\Data;

use App\Enums\CommunicationStatus;

final readonly class ProviderResponse
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public CommunicationStatus $status,
        public ?string $providerMessageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public bool $retryable = false,
        public array $metadata = [],
    ) {}

    public function successful(): bool
    {
        return $this->status->successful();
    }
}
