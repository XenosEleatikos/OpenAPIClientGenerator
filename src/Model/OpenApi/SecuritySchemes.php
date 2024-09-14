<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, SecurityScheme> */
class SecuritySchemes extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $securitySchemes): self
    {
        $instance = new self();

        foreach ((array)$securitySchemes as $name => $securityScheme) {
            $instance[$name] = SecurityScheme::make($securityScheme);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (SecurityScheme $securityScheme) => $securityScheme->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
