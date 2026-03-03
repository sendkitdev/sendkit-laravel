<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Mail;
use SendKit\Client;
use SendKit\Laravel\Transport\SendKitTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

function createTestClient(array &$history, array $responses): Client
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $guzzle = new GuzzleClient([
        'handler' => $stack,
        'base_uri' => 'https://api.sendkit.dev',
        'headers' => [
            'Authorization' => 'Bearer test-api-key',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ]);

    return new Client('test-api-key', http: $guzzle);
}

it('registers the sendkit mail transport', function () {
    $transport = Mail::createSymfonyTransport(['transport' => 'sendkit']);

    expect((string) $transport)->toBe('sendkit');
});

it('sends an email via the transport', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello World</p>');

    $transport->send($email);

    expect($history)->toHaveCount(1);

    $request = $history[0]['request'];
    expect($request->getUri()->getPath())->toBe('/v1/emails/mime');

    $body = json_decode($request->getBody()->getContents(), true);
    expect($body['envelope_from'])->toBe('sender@example.com');
    expect($body['raw_message'])->toContain('Test Subject');
});

it('sends an email with sender display name', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com', 'Support Team'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello World</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['envelope_from'])->toBe('"Support Team" <sender@example.com>');
});

it('casts transport to string as sendkit', function () {
    $history = [];
    $client = createTestClient($history, []);
    $transport = new SendKitTransport($client);

    expect((string) $transport)->toBe('sendkit');
});
