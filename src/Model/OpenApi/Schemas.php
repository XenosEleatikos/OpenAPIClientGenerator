<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @implements ArrayObject<string, Schema> */
class Schemas extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $schemas): self
    {
        $instance = new self();

        foreach ((array)$schemas as $name => $schema) {
            $instance[$name] = Schema::make($schema);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn(Schema $schema) => $schema->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
