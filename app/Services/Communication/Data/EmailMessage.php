<?php

namespace App\Services\Communication\Data;

final readonly class EmailMessage
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $body,
        public string $idempotencyKey,
        public ?string $from = null,
    ) {}
}
