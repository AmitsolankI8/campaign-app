<?php

namespace App\Jobs;

use App\Services\Communication\GenerateCampaignCommunications;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCampaignScheduleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $scheduleId, public int $executionVersion)
    {
        $this->onConnection(config('communication.connection'));
        $this->onQueue('campaigns');
        $this->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(GenerateCampaignCommunications $service): void
    {
        $service->handle($this->scheduleId, $this->executionVersion);
    }

    public function failed(?Throwable $exception): void
    {
        // Work remains durable and recoverable after the dispatch/processing lease expires.
        Log::error('Communication queue job exhausted its retries.', ['job' => self::class, 'exception_type' => $exception ? $exception::class : null]);
    }
}
