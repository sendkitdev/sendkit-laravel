<?php

declare(strict_types=1);

namespace SendKit\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use SendKit\Client;
use SendKit\Laravel\Exceptions\ApiKeyIsMissing;
use SendKit\Laravel\Transport\SendKitTransport;

class SendKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sendkit.php', 'sendkit');

        $this->app->singleton(Client::class, function (): Client {
            $apiKey = config('sendkit.api_key');

            if (! $apiKey) {
                throw ApiKeyIsMissing::create();
            }

            return new Client(
                apiKey: $apiKey,
                baseUrl: config('sendkit.api_url', 'https://api.sendkit.dev'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/sendkit.php' => config_path('sendkit.php'),
        ], 'sendkit-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');

        Mail::extend('sendkit', fn () => new SendKitTransport(
            $this->app->make(Client::class),
        ));
    }
}
