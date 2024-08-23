<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme\OAuth2;

use JsonSerializable;
use stdClass;

class Password implements JsonSerializable
{
    public function __construct(
        public string $tokenUrl,
        /** @var array<string, string> */
        public array $scopes,
        public ?string $refreshUrl = null,
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
