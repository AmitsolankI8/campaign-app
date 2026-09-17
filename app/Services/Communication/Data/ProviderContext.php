<?php

namespace App\Services\Communication\Data;

final readonly class ProviderContext
{
    /** @param array<string, string|null> $credentials */
    public function __construct(
        public int $accountId,
        public string $accountPublicId,
        #[\SensitiveParameter] public array $credentials,
        public string $outcome = 'success',
    ) {}
}
