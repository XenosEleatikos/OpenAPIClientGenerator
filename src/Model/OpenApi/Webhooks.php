<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;

/** @implements ArrayObject<string, PathItem|Reference> */
class Webhooks extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $webhooks): self
    {
        $instance = new self();

        foreach ((array)$webhooks as $name => $pathItemOrReference) {
            if (isset($pathItemOrReference->{'$ref'})) {
                $instance[$name] = Reference::make($pathItemOrReference);
            } else {
                $instance[$name] = PathItem::make($pathItemOrReference);
            }
        }

        return $instance;
    }

    public function jsonSerialize(): mixed
    {
        return array_map(
            fn(PathItem $pathItem) => $pathItem->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
