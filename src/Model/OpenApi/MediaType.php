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
        // @todo Implement specific methods instead of "resolveReference" for better type hinting
        return $this->schema instanceof Reference // @phpstan-ignore-line
            ? $openAPI->resolveReference($this->schema)
            : $this->schema;
    }
}
