<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme\OAuth2;

use JsonSerializable;
use stdClass;
use function array_filter;

class Implicit implements JsonSerializable
{
    public function __construct(
        public string $authorizationUrl,
        /** @var array<string, string> */
        public array $scopes,
        public ?string $refreshUrl = null,
    ) {
    }

    public static function make(stdClass $implicit): self
    {
        return new self(...array_filter([
            'authorizationUrl' => $implicit->authorizationUrl,
            'scopes' => isset($implicit->scopes) ? (array)$implicit->scopes : null,
            'refreshUrl' => $implicit->refreshUrl ?? null,
        ]));
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
