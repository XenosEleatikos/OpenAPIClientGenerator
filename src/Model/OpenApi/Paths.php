<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;
use function array_merge;

/** @implements ArrayObject<string, PathItem> */
class Paths extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $paths): self
    {
        foreach ((array)$paths as $endpoint => $pathItem) {
            $pathItems[$endpoint] = PathItem::make($pathItem);
        }

        return new self($pathItems ?? []);
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_map(
            fn(PathItem $pathItem) => $pathItem->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
