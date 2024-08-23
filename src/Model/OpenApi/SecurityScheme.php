<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeApiKey;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeHttp;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeOAuth2;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\SecuritySchemeType;
use stdClass;

use function array_filter;

class SecurityScheme implements JsonSerializable
{
    protected function __construct(
        public SecuritySchemeType $type,
        public ?string $description =  null,
    ) {
    }

    public static function make(stdClass $securityScheme): self
    {
        return match (SecuritySchemeType::from($securityScheme->type)) {
            SecuritySchemeType::API_KEY => SecuritySchemeApiKey::make($securityScheme),
            SecuritySchemeType::HTTP => SecuritySchemeHttp::make($securityScheme),
            SecuritySchemeType::MUTUAL_TLS => SecuritySchemeApiKey::make($securityScheme),
            SecuritySchemeType::OAUTH2 => SecuritySchemeOAuth2::make($securityScheme),
            SecuritySchemeType::OPEN_ID_CONNECT => SecuritySchemeApiKey::make($securityScheme),
        };
    }

    public static function makeSecuritySchemeOrReference(stdClass $securitySchemeOrReference): self|Reference
    {
        if (isset($securitySchemeOrReference->{'$ref'})) {
            return Reference::make($securitySchemeOrReference);
        } else {
            return SecurityScheme::make($securitySchemeOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'type' => $this->type->jsonSerialize(),
            'description' => $this->description,
        ]);
    }
}
