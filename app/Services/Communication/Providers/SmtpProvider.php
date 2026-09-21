<?php

namespace App\Services\Communication\Providers;

use App\Contracts\Communication\EmailProvider;
use App\Enums\CommunicationStatus;
use App\Services\Communication\Data\EmailMessage;
use App\Services\Communication\Data\ProviderContext;
use App\Services\Communication\Data\ProviderResponse;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SmtpProvider implements EmailProvider
{
    public function __construct(private ProviderContext $context) {}

    public function send(EmailMessage $message): ProviderResponse
    {
        $credentials = $this->context->credentials;
        $encryption = $credentials['encryption'];
        $transport = new EsmtpTransport(
            $credentials['host'],
            (int) $credentials['port'],
            $encryption === 'ssl' ? true : ($encryption === 'none' ? false : null),
        );

        if ($encryption === 'tls') {
            $transport->setRequireTls(true);
        }

        if (filled($credentials['username'] ?? null)) {
            $transport->setUsername($credentials['username']);
        }

        if (filled($credentials['password'] ?? null)) {
            $transport->setPassword($credentials['password']);
        }

        $stream = $transport->getStream();
        if ($stream instanceof SocketStream) {
            $stream->setTimeout((float) config('communication.smtp.timeout_seconds'));
        }

        $email = (new Email)
            ->from(new Address($message->from ?? $credentials['from_address'], $credentials['from_name']))
            ->to($message->to)
            ->subject($message->subject)
            ->text($message->body);
        $email->getHeaders()->addTextHeader('X-Campaign-Idempotency-Key', $message->idempotencyKey);

        $sent = $transport->send($email);

        return new ProviderResponse(
            CommunicationStatus::Sent,
            $sent?->getMessageId(),
            metadata: ['transport' => 'smtp'],
        );
    }
}
