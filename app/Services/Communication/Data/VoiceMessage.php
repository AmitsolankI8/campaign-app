<?php

namespace App\Services\Communication\Data;

final readonly class VoiceMessage
{
    public function __construct(
        public string $to,
        public string $script,
        public string $idempotencyKey,
        public ?string $from = null,
    ) {}
}
