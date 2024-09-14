<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, Response> */
class Responses extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $responses): self
    {
        $instance = new self();

        foreach ((array)$responses as $name => $response) {
            $instance[$name] = Response::make($response);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (Response $response) => $response->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
