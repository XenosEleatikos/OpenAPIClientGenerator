<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;

/** @implements ArrayObject<string, Header|Reference> */
class HeadersOrReferences extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $headersOrReferences): self
    {
        $instance = new self();

        foreach ((array)$headersOrReferences as $name => $headerOrReference) {
            $instance[$name] = Header::makeHeaderOrReference($headerOrReference);
        }

        return $instance;
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn(Header|Reference $header) => $header->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
