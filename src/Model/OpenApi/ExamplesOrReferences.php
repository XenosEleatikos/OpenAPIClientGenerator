<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;

/** @extends ArrayObject<string, Example|Reference> */
class ExamplesOrReferences extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $examplesOrReferences): self
    {
        $instance = new self();

        foreach ((array)$examplesOrReferences as $example) {
            $instance[] = Example::makeExampleOrReference($example);
        }

        return $instance;
    }

    public function jsonSerialize(): mixed
    {
        return array_map(
            fn (Example|Reference $schema) => $schema->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
