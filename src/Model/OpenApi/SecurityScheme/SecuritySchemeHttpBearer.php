<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme;

use JsonSerializable;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeHttp;
use stdClass;

use function array_filter;

class SecuritySchemeHttpBearer extends SecuritySchemeHttp implements JsonSerializable
{
    public function __construct(
        string   $scheme,
        public string $bearerFormat,
        ?string         $description = null,
    ) {
        parent::__construct(
            $scheme,
            $description
        );
    }

    public static function make(stdClass $securityScheme): self
    {
        return new self(
            scheme: $securityScheme->scheme,
            bearerFormat: $securityScheme->bearerFormat,
            description: $securityScheme->description ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'type' => $this->type->jsonSerialize(),
            'description' => $this->description ?? null,
            'scheme' => $this->scheme,
            'bearerFormat' => $this->bearerFormat,
        ]);
    }
}
