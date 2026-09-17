<?php

namespace App\Services\Communication\Data;

use App\Contracts\Communication\EmailProvider;
use App\Contracts\Communication\SmsProvider;
use App\Contracts\Communication\VoiceProvider;

final readonly class ResolvedProvider
{
    public function __construct(
        public int $accountId,
        public SmsProvider|VoiceProvider|EmailProvider $adapter,
        public bool $simulated = false,
    ) {}
}
