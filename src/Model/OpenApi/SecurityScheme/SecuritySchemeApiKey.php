<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme;

use JsonSerializable;
use OpenApiClientGenerator\Model\OpenApi\ApiKeyIn;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeType;
use stdClass;

use function array_filter;

class SecuritySchemeApiKey extends SecurityScheme implements JsonSerializable
{
    public function __construct(
        public string   $name,
        public ApiKeyIn $in,
        ?string         $description = null,
    ) {
        parent::__construct(
            SecuritySchemeType::API_KEY,
            $description
        );
    }

    public static function make(stdClass $securityScheme): self
    {
        return new self(
            name: $securityScheme->name,
            in: ApiKeyIn::from($securityScheme->in),
            description: $securityScheme->description ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'type' => $this->type->jsonSerialize(),
            'description' => $this->description,
            'name' => $this->name,
            'in' => $this->in,
        ]);
    }
}
