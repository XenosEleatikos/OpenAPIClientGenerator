<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, Parameter> */
class Parameters extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $parameters): self
    {
        $instance = new self();

        foreach ((array)$parameters as $name => $parameter) {
            $instance[$name] = Parameter::make($parameter);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (Parameter $parameter) => $parameter->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
