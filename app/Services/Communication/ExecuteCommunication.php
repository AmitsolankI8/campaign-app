<?php

namespace App\Services\Communication;

use App\Models\Communication;
use App\Services\Communication\Data\CommunicationRequest;

class ExecuteCommunication
{
    public function __construct(private CommunicationManager $manager, private StaticMessageFactory $messages) {}

    public function handle(int $id, int $version): void
    {
        $communication = Communication::query()->with(['contact', 'channel'])->find($id);
        if (! $communication) {
            return;
        }
        $channel = $communication->channel->code;
        if (! in_array($channel, ['sms', 'voice', 'email'], true)) {
            $this->manager->reject($id, $version, 'unsupported_channel');

            return;
        }
        // Invalid/missing destinations are rejected by the manager under the campaign lock.
        $destination = $channel === 'email' ? $communication->contact?->email : $communication->contact?->number;
        $message = $this->messages->make($channel, $destination ?? '', $communication->idempotency_key);
        $this->manager->send(new CommunicationRequest($id, $version, $message));
    }
}
