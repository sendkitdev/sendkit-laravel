<?php

declare(strict_types=1);

namespace SendKit\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SendKit\Client;

/**
 * @method static \SendKit\Emails emails()
 *
 * @see \SendKit\Client
 */
class SendKit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
