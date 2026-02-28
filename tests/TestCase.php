<?php

declare(strict_types=1);

namespace SendKit\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SendKit\Laravel\SendKitServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SendKitServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sendkit.api_key', 'test-api-key');
        $app['config']->set('sendkit.webhook.secret', 'test-webhook-secret');
    }
}
