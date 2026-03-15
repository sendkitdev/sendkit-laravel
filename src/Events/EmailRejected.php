<?php

declare(strict_types=1);

namespace SendKit\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

class EmailRejected
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
    ) {}
}
