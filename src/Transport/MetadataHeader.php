<?php

declare(strict_types=1);

namespace SendKit\Laravel\Transport;

use Symfony\Component\Mime\Header\UnstructuredHeader;

class MetadataHeader extends UnstructuredHeader
{
    private string $key;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;

        parent::__construct('X-Metadata-'.$key, $value);
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
