<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

class Encoding implements JsonSerializable
{
    public function __construct(
        public string              $contentType,
        public HeadersOrReferences $headers = new HeadersOrReferences(),
        public ?string             $style = null,
    ) {
    }

    public static function make(stdClass $encoding): self
    {
        return new self(
            contentType: $encoding->contentType,
            headers: HeadersOrReferences::make($encoding->headers ?? []),
            style: $encoding->style ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)[
            'contentType' => $this->contentType,
            'header' => $this->headers->jsonSerialize(),
            'style' => $this->style,
        ];
    }
}
