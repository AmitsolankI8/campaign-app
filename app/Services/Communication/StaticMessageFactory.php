<?php

namespace App\Services\Communication;

use App\Services\Communication\Data\EmailMessage;
use App\Services\Communication\Data\SmsMessage;
use App\Services\Communication\Data\VoiceMessage;
use InvalidArgumentException;

class StaticMessageFactory
{
    public function make(string $channel, string $destination, string $idempotencyKey): SmsMessage|VoiceMessage|EmailMessage
    {
        $content = 'This is a test campaign message.';

        return match ($channel) {
            'sms' => new SmsMessage($destination, $content, $idempotencyKey),
            'voice' => new VoiceMessage($destination, $content, $idempotencyKey),
            'email' => new EmailMessage($destination, 'Campaign test message', $content, $idempotencyKey),
            default => throw new InvalidArgumentException('Unsupported communication channel.'),
        };
    }
}
