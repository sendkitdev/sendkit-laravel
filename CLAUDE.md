# SendKit Laravel Package

## Project Overview

Laravel integration for SendKit. Registers a `sendkit` mail transport and handles webhooks.

## Architecture

```
src/
├── SendKitServiceProvider.php          # Registers config, client singleton, mail transport, webhook route
├── Facades/
│   └── SendKit.php                     # Facade for SendKit\Client
├── Transport/
│   └── SendKitTransport.php            # Symfony AbstractTransport → calls sendMime()
├── Http/
│   ├── Controllers/
│   │   └── WebhookController.php       # Maps webhook event types to Laravel events
│   └── Middleware/
│       └── VerifyWebhookSignature.php  # HMAC-SHA256 signature validation
├── Events/
│   ├── EmailSent.php
│   ├── EmailDelivered.php
│   ├── EmailBounced.php
│   ├── EmailComplained.php
│   ├── EmailOpened.php
│   ├── EmailClicked.php
│   ├── EmailFailed.php
│   └── EmailDeliveryDelayed.php
└── Exceptions/
    └── ApiKeyIsMissing.php
```

## Key Decisions

- Transport uses `/v1/emails/mime` endpoint (Symfony Mailer provides full MIME message)
- Webhook route is auto-registered at `POST /webhook/sendkit` (configurable via `SENDKIT_WEBHOOK_PATH`)
- Signature verification uses `hash_hmac('sha256', $jsonBody, $secret)` matching SendKit's `DispatchWebhook` job
- Signature verification is skipped if `SENDKIT_WEBHOOK_SECRET` is not set
- Events receive `array $payload` (the `data` field from the webhook body)

## Dependencies

- PHP ^8.2
- illuminate/support ^11.0|^12.0
- sendkit/sendkit-php ^0.1
- symfony/mailer ^7.0
- orchestra/testbench ^10.0 (dev)
- pestphp/pest ^3.0 (dev)

## PHP Conventions

- Always use `declare(strict_types=1)` at the top of every PHP file
- Always use explicit return type declarations
- Use PHP 8 constructor property promotion
- Use `readonly` properties where appropriate
- Prefer PHPDoc blocks over inline comments

## Testing

- Tests use Pest 3 + Orchestra Testbench
- Run tests: `vendor/bin/pest`
- `TestCase` base class sets `sendkit.api_key` and `sendkit.webhook.secret` config
- Transport tests use Guzzle `MockHandler` injected via `Client` constructor
- Webhook tests use `Event::fake()` and sign payloads with `hash_hmac()`

## Config

```php
// config/sendkit.php
'api_key'  => env('SENDKIT_API_KEY'),
'api_url'  => env('SENDKIT_API_URL', 'https://api.sendkit.dev'),
'webhook'  => [
    'secret'    => env('SENDKIT_WEBHOOK_SECRET'),
    'path'      => env('SENDKIT_WEBHOOK_PATH', 'webhook/sendkit'),
    'tolerance' => 300,
],
```

## Releasing

- Tags use numeric format: `0.1.0`, `0.2.0`, `1.0.0` (no `v` prefix)
- CI runs tests on PHP 8.2, 8.3, 8.4
- Pushing a tag triggers tests → Packagist update
- Always tag `sendkit-php` first if both packages have changes

## Git

- NEVER add `Co-Authored-By` lines to commit messages
