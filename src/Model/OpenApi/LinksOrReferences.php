<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, Link|Reference> */
class LinksOrReferences extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $links): self
    {
        $instance = new self();

        foreach ((array)$links as $name => $linkOrReference) {
            $instance[$name] = Link::makeLinkOrReference($linkOrReference);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (Link|Reference $link) => $link->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }
}
