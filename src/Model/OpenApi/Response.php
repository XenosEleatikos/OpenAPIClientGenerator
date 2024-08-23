<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;

use stdClass;

use function array_filter;

class Response implements JsonSerializable
{
    public function __construct(
        public string             $description,
        public ?HeadersOrReferences $headers = null,
        public ?MediaTypes          $content = null,
        public ?LinksOrReferences $links = null,
    ) {
    }

    public static function make(stdClass $requestBody): self
    {
        return new self(
            description: $requestBody->description,
            headers: isset($requestBody->headers) ? HeadersOrReferences::make($requestBody->headers) : null,
            content: isset($requestBody->content) ? MediaTypes::make($requestBody->content) : null,
            links: isset($requestBody->links) ? LinksOrReferences::make($requestBody->links) : null,
        );
    }

    public static function makeResponseOrReference(stdClass $responseOrReference): self|Reference
    {
        if (isset($responseOrReference->{'$ref'})) {
            return Reference::make($responseOrReference);
        } else {
            return self::make($responseOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'description' => $this->description,
            'headers' => $this->headers?->jsonSerialize(),
            'content' => $this->content?->jsonSerialize(),
            'links' => $this->links?->jsonSerialize(),
        ]);
    }
}
