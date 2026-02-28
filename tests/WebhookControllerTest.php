<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SendKit\Laravel\Events\EmailBounced;
use SendKit\Laravel\Events\EmailClicked;
use SendKit\Laravel\Events\EmailComplained;
use SendKit\Laravel\Events\EmailDelivered;
use SendKit\Laravel\Events\EmailDeliveryDelayed;
use SendKit\Laravel\Events\EmailFailed;
use SendKit\Laravel\Events\EmailOpened;
use SendKit\Laravel\Events\EmailSent;

function signPayload(array $payload, string $secret = 'test-webhook-secret'): string
{
    return hash_hmac('sha256', json_encode($payload), $secret);
}

it('rejects requests without a signature', function () {
    $payload = ['type' => 'email.sent', 'data' => ['email_id' => 'abc']];

    $this->postJson(route('sendkit.webhook'), $payload)
        ->assertForbidden();
});

it('rejects requests with an invalid signature', function () {
    $payload = ['type' => 'email.sent', 'data' => ['email_id' => 'abc']];

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => 'invalid-signature',
    ])->assertForbidden();
});

it('accepts requests with a valid signature', function () {
    Event::fake();

    $payload = ['type' => 'email.sent', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();
});

it('dispatches EmailSent event', function () {
    Event::fake();

    $payload = ['type' => 'email.sent', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailSent::class, fn ($event) => $event->payload === ['email_id' => 'abc']);
});

it('dispatches EmailDelivered event', function () {
    Event::fake();

    $payload = ['type' => 'email.delivered', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailDelivered::class);
});

it('dispatches EmailBounced event', function () {
    Event::fake();

    $payload = ['type' => 'email.bounced', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailBounced::class);
});

it('dispatches EmailComplained event', function () {
    Event::fake();

    $payload = ['type' => 'email.complained', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailComplained::class);
});

it('dispatches EmailOpened event', function () {
    Event::fake();

    $payload = ['type' => 'email.opened', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailOpened::class);
});

it('dispatches EmailClicked event', function () {
    Event::fake();

    $payload = ['type' => 'email.clicked', 'data' => ['email_id' => 'abc', 'url' => 'https://example.com']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailClicked::class);
});

it('dispatches EmailFailed event', function () {
    Event::fake();

    $payload = ['type' => 'email.failed', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailFailed::class);
});

it('dispatches EmailDeliveryDelayed event', function () {
    Event::fake();

    $payload = ['type' => 'email.delivery_delayed', 'data' => ['email_id' => 'abc']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertDispatched(EmailDeliveryDelayed::class);
});

it('returns ok for unknown event types', function () {
    Event::fake();

    $payload = ['type' => 'unknown.event', 'data' => ['foo' => 'bar']];
    $signature = signPayload($payload);

    $this->postJson(route('sendkit.webhook'), $payload, [
        'X-Webhook-Signature' => $signature,
    ])->assertOk();

    Event::assertNotDispatched(EmailSent::class);
    Event::assertNotDispatched(EmailDelivered::class);
    Event::assertNotDispatched(EmailBounced::class);
    Event::assertNotDispatched(EmailComplained::class);
    Event::assertNotDispatched(EmailOpened::class);
    Event::assertNotDispatched(EmailClicked::class);
    Event::assertNotDispatched(EmailFailed::class);
    Event::assertNotDispatched(EmailDeliveryDelayed::class);
});

it('skips signature verification when secret is not configured', function () {
    Event::fake();

    config()->set('sendkit.webhook.secret', null);

    $payload = ['type' => 'email.sent', 'data' => ['email_id' => 'abc']];

    $this->postJson(route('sendkit.webhook'), $payload)
        ->assertOk();

    Event::assertDispatched(EmailSent::class);
});
