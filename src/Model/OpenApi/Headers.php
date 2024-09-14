<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, Header> */
class Headers extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $headers): self
    {
        $instance = new self();

        foreach ((array)$headers as $name => $header) {
            $instance[$name] = Header::make($header);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (Header $header) => $header->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
