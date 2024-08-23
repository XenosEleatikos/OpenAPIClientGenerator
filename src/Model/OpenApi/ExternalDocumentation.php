<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class ExternalDocumentation implements JsonSerializable
{
    public function __construct(
        public string $url,
        public ?string $description = null,
    ) {
    }

    public static function make(stdClass $schema): self
    {
        return new self(
            url: $schema->url,
            description: $schema->description ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
