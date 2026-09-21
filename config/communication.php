<?php

return [
    'connection' => env('COMMUNICATION_QUEUE_CONNECTION', 'redis'),
    'simulation' => [
        'enabled' => (bool) env('COMMUNICATION_SIMULATION', false),
        // Keys are account public IDs or channel.provider codes; values: success, failed, retryable, unknown.
        'outcomes' => [],
    ],
    'smtp' => [
        'timeout_seconds' => (float) env('COMMUNICATION_SMTP_TIMEOUT', 15),
    ],
    'chunk_size' => 500,
    'recovery_limit' => 100,
    'lease_seconds' => 180,
    'max_retries' => 3,
    'backoff' => [30, 120, 300],
];
