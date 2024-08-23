<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme;

use JsonSerializable;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\OAuth2\OAuthFlows;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme;
use stdClass;

use function array_filter;

class SecuritySchemeOAuth2 extends SecurityScheme implements JsonSerializable
{
    public function __construct(
        public OAuthFlows $flows,
        ?string         $description = null,
    ) {
        parent::__construct(
            SecuritySchemeType::OAUTH2,
            $description
        );
    }

    public static function make(stdClass $securityScheme): self
    {
        return new self(
            flows: OAuthFlows::make($securityScheme->flows),
            description: $securityScheme->description ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'type' => $this->type->jsonSerialize(),
            'description' => $this->description,
            'flows' => $this->flows->jsonSerialize(),
        ]);
    }
}
