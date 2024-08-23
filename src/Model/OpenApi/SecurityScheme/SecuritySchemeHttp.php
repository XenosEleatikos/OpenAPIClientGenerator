<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme;

use JsonSerializable;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeHttpBearer;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeType;
use stdClass;

use function array_filter;

class SecuritySchemeHttp extends SecurityScheme implements JsonSerializable
{
    public function __construct(
        public string   $scheme,
        ?string         $description = null,
    ) {
        parent::__construct(
            SecuritySchemeType::HTTP,
            $description
        );
    }

    public static function make(stdClass $securityScheme): self
    {
        $scheme = $securityScheme->scheme;
        if (preg_match('#^[Bb][Ee][Aa][Rr][Ee][Rr]$#', $scheme)){
            return SecuritySchemeHttpBearer::make($securityScheme);
        }

        return new self(
            scheme: $securityScheme->scheme,
            description: $securityScheme->description ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'type' => $this->type->jsonSerialize(),
            'description' => $this->description ?? null,
            'scheme' => $this->scheme,
        ]);
    }
}
