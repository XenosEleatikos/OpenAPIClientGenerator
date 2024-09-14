<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;

/** @extends ArrayObject<string, PathItem|Reference> */
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

    /** @return array<string, stdClass> */
    public function jsonSerialize(): array
    {
        return array_map(
            fn (PathItem|Reference $pathItemOrReference) => $pathItemOrReference->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
