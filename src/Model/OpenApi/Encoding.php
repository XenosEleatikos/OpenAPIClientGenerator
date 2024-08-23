<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

class Encoding implements JsonSerializable
{
    public function __construct(
        public string              $contentType,
        public HeadersOrReferences $header = new HeadersOrReferences(),
        public ?string             $style = null,
    ) {
    }

    public static function make(stdClass $encoding): self
    {
        return new self(
            contentType: $encoding->contentType,
            header: isset($encoding->headers) ? HeadersOrReferences::make($encoding->headers) : null,
            style: $encoding->headers ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)[
            'properties' => $this->properties->jsonSerialize(),
        ];
    }
}
