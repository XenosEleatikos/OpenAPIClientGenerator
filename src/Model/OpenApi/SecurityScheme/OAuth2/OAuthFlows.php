<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi\SecurityScheme\OAuth2;

use JsonSerializable;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\OAuth2\ClientCredentials;
use OpenApiClientGenerator\Model\OpenApi\SecurityScheme\OAuth2\Password;
use stdClass;
use function array_filter;

class OAuthFlows implements JsonSerializable
{
    public function __construct(
        public ?Implicit $implicit = null,
        public ?Password $password = null,
        public ?ClientCredentials $clientCredentials = null,
    ) {
    }

    public static function make(stdClass $oAuthFlows): self
    {
        return new self(...array_filter([
            'implicit' => isset($oAuthFlows->implicit) ? Implicit::make($oAuthFlows->implicit) : null,
            'password' => isset($oAuthFlows->password) ? Password::make($oAuthFlows->password) : null,
            'clientCredential' => isset($oAuthFlows->clientCredential) ? ClientCredentials::make($oAuthFlows->clientCredential) : null,
        ]));
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
