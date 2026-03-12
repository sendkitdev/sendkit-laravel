<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Mail;
use SendKit\Client;
use SendKit\Laravel\Transport\MetadataHeader;
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
    expect($body['to'])->toBe(['recipient@example.com']);
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
    expect($body['to'])->toBe(['recipient@example.com']);
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

it('sends reply_to as an array with multiple addresses', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->replyTo(new Address('reply@example.com'), new Address('reply2@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['reply_to'])->toBeArray();
    expect($body['reply_to'])->toBe(['reply@example.com', 'reply2@example.com']);
});

it('sends an email with tags via MetadataHeader', function () {
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

    $email->getHeaders()->add(new MetadataHeader('campaign', 'welcome'));
    $email->getHeaders()->add(new MetadataHeader('environment', 'production'));

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['tags'])->toHaveCount(2);
    expect($body['tags'][0])->toBe(['name' => 'campaign', 'value' => 'welcome']);
    expect($body['tags'][1])->toBe(['name' => 'environment', 'value' => 'production']);
});

it('does not include tags when none are set', function () {
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
    expect($body)->not->toHaveKey('tags');
});

it('sends an email with scheduled_at via X-SendKit-Scheduled-At header', function () {
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

    $email->getHeaders()->addTextHeader('X-SendKit-Scheduled-At', '2026-12-25T10:00:00Z');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['scheduled_at'])->toBe('2026-12-25T10:00:00Z');
    expect($body)->not->toHaveKey('headers');
});

it('sends an email with attachments', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>')
        ->attach('file content here', 'document.txt', 'text/plain');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['attachments'])->toHaveCount(1);
    expect($body['attachments'][0]['filename'])->toBe('document.txt');
    expect($body['attachments'][0]['content_type'])->toBe('text/plain');
    expect($body['attachments'][0]['content'])->toBeString();
});

it('forwards custom headers', function () {
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

    $email->getHeaders()->addTextHeader('X-Custom-Header', 'custom-value');
    $email->getHeaders()->addTextHeader('X-Another-Header', 'another-value');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['headers'])->toHaveKey('X-Custom-Header');
    expect($body['headers']['X-Custom-Header'])->toBe('custom-value');
    expect($body['headers']['X-Another-Header'])->toBe('another-value');
});

it('sends an email with multiple to recipients', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient1@example.com'), new Address('recipient2@example.com'), new Address('recipient3@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['to'])->toBe(['recipient1@example.com', 'recipient2@example.com', 'recipient3@example.com']);
});

it('throws a TransportException when the API returns an error', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(422, [], json_encode(['message' => 'Validation failed'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);
})->throws(\Symfony\Component\Mailer\Exception\TransportException::class);

it('sends an email with multiple cc addresses', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->cc(new Address('cc1@example.com'), new Address('cc2@example.com'), new Address('cc3@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['cc'])->toBeArray();
    expect($body['cc'])->toBe(['cc1@example.com', 'cc2@example.com', 'cc3@example.com']);
});

it('sends an email with multiple bcc addresses', function () {
    $history = [];
    $client = createTestClient($history, [
        new Response(200, [], json_encode(['id' => 'sent-email-uuid'])),
    ]);

    $transport = new SendKitTransport($client);

    $email = (new Email)
        ->from(new Address('sender@example.com'))
        ->to(new Address('recipient@example.com'))
        ->bcc(new Address('bcc1@example.com'), new Address('bcc2@example.com'), new Address('bcc3@example.com'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $transport->send($email);

    $body = json_decode($history[0]['request']->getBody()->getContents(), true);
    expect($body['bcc'])->toBeArray();
    expect($body['bcc'])->toBe(['bcc1@example.com', 'bcc2@example.com', 'bcc3@example.com']);
});
