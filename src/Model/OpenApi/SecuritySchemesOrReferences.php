<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @implements ArrayObject<string, SecurityScheme|Reference> */
class SecuritySchemesOrReferences extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $securitySchemes): self
    {
        $instance = new self();

        foreach ((array)$securitySchemes as $name => $securitySchemeOrReference) {
            $instance[$name] = SecurityScheme::makeSecuritySchemeOrReference($securitySchemeOrReference);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn(SecurityScheme|Reference $securityScheme) => $securityScheme->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
