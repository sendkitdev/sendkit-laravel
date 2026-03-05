# SendKit for Laravel

Official Laravel integration for [SendKit](https://sendkit.com). Adds a `sendkit` mail transport and webhook handling.

## Installation

```bash
composer require sendkit/sendkit-laravel
```

## Configuration

Add your API key to `.env`:

```env
MAIL_MAILER=sendkit
SENDKIT_API_KEY=your-api-key
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=sendkit-config
```

## Usage

### Sending Emails

Use Laravel's standard mail system — it just works:

```php
Mail::to('recipient@example.com')->send(new WelcomeEmail());
```

### Using the SDK Directly

```php
use SendKit\Laravel\Facades\SendKit;

$response = SendKit::emails()->send([
    'from' => 'you@example.com',
    'to' => 'recipient@example.com',
    'subject' => 'Hello',
    'html' => '<h1>Welcome!</h1>',
]);
```

### Validating an Email

```php
use SendKit\Laravel\Facades\SendKit;

$result = SendKit::validateEmail('recipient@example.com');

$result['is_valid'];       // true or false
$result['should_block'];   // true if the email should be blocked
$result['block_reason'];   // reason for blocking, or null
$result['evaluations'];    // detailed evaluation results
```

The `evaluations` array contains:
- `has_valid_syntax` — whether the email has valid syntax
- `has_valid_dns` — whether the domain has valid DNS records
- `mailbox_exists` — whether the mailbox exists
- `is_role_address` — whether it's a role address (e.g. info@, admin@)
- `is_disposable` — whether it's a disposable email
- `is_random_input` — whether it appears to be random input

> **Note:** Each validation costs credits. A `SendKitException` with status 402 is thrown when credits are insufficient.

## Webhooks

Webhook handling is auto-registered at `POST /webhook/sendkit`.

### Setup

Add your webhook secret to `.env`:

```env
SENDKIT_WEBHOOK_SECRET=your-webhook-secret
```

### Listening for Events

```php
use SendKit\Laravel\Events\EmailDelivered;

Event::listen(EmailDelivered::class, function ($event) {
    // $event->payload contains the webhook data
});
```

### Available Events

- `EmailSent` — `email.sent`
- `EmailDelivered` — `email.delivered`
- `EmailBounced` — `email.bounced`
- `EmailComplained` — `email.complained`
- `EmailOpened` — `email.opened`
- `EmailClicked` — `email.clicked`
- `EmailFailed` — `email.failed`
- `EmailDeliveryDelayed` — `email.delivery_delayed`
