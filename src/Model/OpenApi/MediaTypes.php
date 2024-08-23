<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;

/** @implements ArrayObject<string, MediaType|Reference> */
class MediaTypes extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $schemas): self
    {
        $instance = new self();

        foreach ((array)$schemas as $name => $mediaType) {
            $instance[$name] = MediaType::make($mediaType);
        }

        return $instance;
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn(MediaType|Reference $mediaType) => $mediaType->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
