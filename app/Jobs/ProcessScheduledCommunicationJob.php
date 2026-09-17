<?php

namespace App\Jobs;

use App\Services\Communication\ExecuteScheduledCommunication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessScheduledCommunicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $scheduledCommunicationId, public int $executionVersion, public int $version)
    {
        $this->onConnection(config('communication.connection'));
        $this->onQueue('communications');
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(ExecuteScheduledCommunication $service): void
    {
        $service->handle($this->scheduledCommunicationId, $this->executionVersion, $this->version);
    }

    public function failed(?Throwable $exception): void
    {
        // Work remains durable and recoverable after the dispatch/processing lease expires.
        Log::error('Communication queue job exhausted its retries.', ['job' => self::class, 'exception_type' => $exception ? $exception::class : null]);
    }
}
