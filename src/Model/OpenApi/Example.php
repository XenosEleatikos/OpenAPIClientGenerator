<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Example implements JsonSerializable
{
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public mixed $value = null,
        public ?string $externalValue = null,
    ) {
    }

    public static function make(stdClass $example): self
    {
        return new self(
            summary: $example->summary ?? null,
            description: $example->description ?? null,
            value: $example->value ?? null,
            externalValue: $example->externalValue ?? null,
        );
    }

    public static function makeExampleOrReference(stdClass $exampleOrReference): self|Reference
    {
        if (isset($exampleOrReference->{'$ref'})) {
            return Reference::make($exampleOrReference);
        } else {
            return self::make($exampleOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
