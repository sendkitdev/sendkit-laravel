<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use SendKit\Client;
use SendKit\Exceptions\SendKitException;
use SendKit\Laravel\Facades\SendKit;

function createValidationClient(array &$history, array $responses): Client
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

it('validates an email via the facade', function () {
    $history = [];
    $client = createValidationClient($history, [
        new Response(200, [], json_encode([
            'email' => 'user@example.com',
            'is_valid' => true,
            'evaluations' => [
                'has_valid_syntax' => true,
                'has_valid_dns' => true,
                'mailbox_exists' => true,
                'is_role_address' => false,
                'is_disposable' => false,
                'is_random_input' => false,
            ],
            'should_block' => false,
            'block_reason' => null,
            'validated_at' => '2026-03-05 12:00:00',
        ])),
    ]);

    $this->app->instance(Client::class, $client);

    $result = SendKit::validateEmail('user@example.com');

    expect($result['email'])->toBe('user@example.com');
    expect($result['is_valid'])->toBeTrue();
    expect($result['evaluations']['has_valid_syntax'])->toBeTrue();
    expect($result['should_block'])->toBeFalse();
    expect($history)->toHaveCount(1);

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->getPath())->toBe('/emails/validate');
});

it('validates an email via the facade emailValidations method', function () {
    $history = [];
    $client = createValidationClient($history, [
        new Response(200, [], json_encode([
            'email' => 'test@example.com',
            'is_valid' => false,
            'evaluations' => [
                'has_valid_syntax' => true,
                'has_valid_dns' => false,
                'mailbox_exists' => false,
                'is_role_address' => false,
                'is_disposable' => true,
                'is_random_input' => false,
            ],
            'should_block' => true,
            'block_reason' => 'disposable',
            'validated_at' => '2026-03-05 12:00:00',
        ])),
    ]);

    $this->app->instance(Client::class, $client);

    $result = SendKit::emailValidations()->validate('test@example.com');

    expect($result['is_valid'])->toBeFalse();
    expect($result['evaluations']['is_disposable'])->toBeTrue();
    expect($result['should_block'])->toBeTrue();
    expect($result['block_reason'])->toBe('disposable');
});

it('throws exception when validation credits are insufficient', function () {
    $history = [];
    $client = createValidationClient($history, [
        new Response(402, [], json_encode(['message' => 'Insufficient validation credits.'])),
    ]);

    $this->app->instance(Client::class, $client);

    SendKit::validateEmail('user@example.com');
})->throws(SendKitException::class, 'Insufficient validation credits.');
