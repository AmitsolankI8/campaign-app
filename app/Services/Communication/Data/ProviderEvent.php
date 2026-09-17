<?php

namespace App\Services\Communication\Data;

use App\Enums\CommunicationStatus;

final readonly class ProviderEvent
{
    public function __construct(
        public int $providerAccountId,
        public string $providerMessageId,
        public CommunicationStatus $status,
        public string $occurredAt,
    ) {}
}
