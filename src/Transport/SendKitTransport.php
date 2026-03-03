<?php

declare(strict_types=1);

namespace SendKit\Laravel\Transport;

use SendKit\Client;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class SendKitTransport extends AbstractTransport
{
    public function __construct(
        private readonly Client $client,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $from = $envelope->getSender()->toString();
        $to = implode(',', array_map(
            fn (Address $address): string => $address->getAddress(),
            $envelope->getRecipients(),
        ));

        $response = $this->client->emails()->sendMime($from, $to, $message->toString());

        $message->getOriginalMessage()->getHeaders()->addTextHeader(
            'X-SendKit-Email-Id',
            $response['id'],
        );
    }

    public function __toString(): string
    {
        return 'sendkit';
    }
}
