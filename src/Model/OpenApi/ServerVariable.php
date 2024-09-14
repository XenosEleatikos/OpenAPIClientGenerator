<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

class ServerVariable implements JsonSerializable
{
    public function __construct(
        public string $default,
        /** @var string[] $enum */
        public ?array $enum = null,
        public ?string $description = null,
    ) {
    }

    public static function make(stdClass $server): self
    {
        return new self(...(array)$server);
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
