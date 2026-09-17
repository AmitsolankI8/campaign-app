<?php

namespace App\Jobs;

use App\Services\Communication\ApplyProviderEvent;
use App\Services\Communication\Data\ProviderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessProviderEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public ProviderEvent $event)
    {
        $this->onConnection(config('communication.connection'))->onQueue('webhooks')->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ApplyProviderEvent $handler): void
    {
        $handler->handle($this->event);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Provider event processing failed.', ['provider_account_id' => $this->event->providerAccountId]);
    }
}
