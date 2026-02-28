<?php

declare(strict_types=1);

namespace SendKit\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('sendkit.webhook.secret');

        if (! $secret) {
            return $next($request);
        }

        $signature = $request->header('X-Webhook-Signature');

        if (! $signature) {
            abort(403, 'Missing webhook signature.');
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
