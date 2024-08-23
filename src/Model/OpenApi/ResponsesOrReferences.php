<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @implements ArrayObject<string, Response|Reference> */
class ResponsesOrReferences extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $responses): self
    {
        $instance = new self();

        foreach ((array)$responses as $name => $response) {
            $instance[$name] = Response::makeResponseOrReference($response);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn(Response|Reference $response) => $response->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
