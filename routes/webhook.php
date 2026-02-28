<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SendKit\Laravel\Http\Controllers\WebhookController;
use SendKit\Laravel\Http\Middleware\VerifyWebhookSignature;

Route::post(config('sendkit.webhook.path', 'webhook/sendkit'), WebhookController::class)
    ->middleware(VerifyWebhookSignature::class)
    ->name('sendkit.webhook');
