<?php

declare(strict_types=1);

namespace SendKit\Laravel\Exceptions;

use InvalidArgumentException;

class ApiKeyIsMissing extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('The SendKit API key is missing. Set the SENDKIT_API_KEY environment variable.');
    }
}
