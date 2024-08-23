<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;

use stdClass;

class Link implements JsonSerializable
{
    public function __construct(
        public string  $operationRef,
        public ?string $operationId = null,
        public array $parameters = [],
        public mixed $requestBody = null,
        public ?string $description = null,
        public ?Server $body = null,

    ) {
    }

    public static function make(stdClass $license): self
    {
        return new self(...(array)$license);
    }

    public static function makeLinkOrReference(stdClass $linkOrReference): self|Reference
    {
        if (isset($linkOrReference->{'$ref'})) {
            return Reference::make($linkOrReference);
        } else {
            return self::make($linkOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
