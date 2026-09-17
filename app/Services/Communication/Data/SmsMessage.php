<?php

namespace App\Services\Communication\Data;

final readonly class SmsMessage
{
    public function __construct(
        public string $to,
        public string $body,
        public string $idempotencyKey,
        public ?string $from = null,
    ) {}
}
