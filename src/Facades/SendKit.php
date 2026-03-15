<?php

declare(strict_types=1);

namespace SendKit\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SendKit\Client;

/**
 * @method static \SendKit\Contacts contacts()
 * @method static \SendKit\ContactProperties contactProperties()
 * @method static \SendKit\Emails emails()
 * @method static \SendKit\EmailValidations emailValidations()
 * @method static array validateEmail(string $email)
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
