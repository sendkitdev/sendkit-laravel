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
    expect($request->getUri()->getPath())->toBe('/emails');

    $body = json_decode($request->getBody()->getContents(), true);
    expect($body['from'])->toBe('sender@example.com');
    expect($body['to'])->toBe('recipient@example.com');
    expect($body['subject'])->toBe('Test Subject');
    expect($body['html'])->toBe('<p>Hello World</p>');
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
    expect($body['from'])->toBe('"Support Team" <sender@example.com>');
});

it('sends an email with cc and bcc', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->cc(new Address('cc@example.com'))
        ->bcc(new Address('bcc@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['to'])->toBe('recipient@example.com');
    expect($body['cc'])->toBe(['cc@example.com']);
    expect($body['bcc'])->toBe(['bcc@example.com']);
});

it('sends an email with reply-to', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->replyTo(new Address('reply@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['reply_to'])->toBe(['reply@example.com']);
});

it('sends an email with text body', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->text('Plain text content');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['text'])->toBe('Plain text content');
    expect($body)->not->toHaveKey('html');
});

it('omits optional fields when not set', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body)->not->toHaveKey('cc');
    expect($body)->not->toHaveKey('bcc');
    expect($body)->not->toHaveKey('reply_to');
    expect($body)->not->toHaveKey('attachments');
});

it('adds X-SendKit-Email-Id header after sending', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'email-uuid-123'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $sentMessage = $transport->send($email);

    expect($sentMessage->getOriginalMessage()->getHeaders()->get('X-SendKit-Email-Id')->getBody())->toBe('email-uuid-123');
});

it('casts transport to string as sendkit', function () {
    $history = [];
    $client = createTestClient($history, []);
    $transport = new SendKitTransport($client);

    expect((string) $transport)->toBe('sendkit');
});
