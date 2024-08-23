<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class MediaType implements JsonSerializable
{
    public function __construct(
        public null|Schema|Reference $schema = null,
        public mixed                 $example = null,
        public ?ExamplesOrReferences $examples = null,
    ) {
    }

    public static function make(stdClass $mediaType): self
    {
        return new self(
            schema: isset($mediaType->schema) ? Schema::makeSchemaOrReference($mediaType->schema) : null,
            example: $mediaType->example ?? null,
            examples: isset($mediaType->examples) ? ExamplesOrReferences::make($mediaType->examples) : null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }

    public function resolveSchema(OpenAPI $openAPI): Schema
    {
        return $this->schema instanceof Reference
            ? $openAPI->resolveReference($this->schema)
            : $this->schema;
    }
}
