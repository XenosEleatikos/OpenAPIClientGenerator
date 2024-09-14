<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Reference implements JsonSerializable
{
    public function __construct(
        public string $ref,
        public ?string $summary = null,
        public ?string $description = null,
    ) {
    }

    public static function make(stdClass $reference): self
    {
        return new self(
            ref: $reference->{'$ref'},
            summary: $reference->summary ?? null,
            description: $reference->description ?? null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            '$ref' => $this->ref,
            'summary' => $this->summary,
            'description' => $this->description,
        ]);
    }
}
