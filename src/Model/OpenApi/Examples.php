<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, Example> */
class Examples extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $examples): self
    {
        $instance = new self();

        foreach ((array)$examples as $name => $example) {
            $instance[$name] = Example::make($example);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (Example $example) => $example->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
