<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;

use function array_map;
use function is_string;

/** @implements ArrayObject<int, SchemaType> */
class SchemaTypes extends ArrayObject implements JsonSerializable
{
    public static function make(string|array $schemaTypes): self
    {
        if (is_string($schemaTypes)) {
            $schemaTypes = [$schemaTypes];
        }

        return new self(
            array_map(
                fn(string $schemaType): SchemaType => SchemaType::from($schemaType),
                $schemaTypes
            )
        );
    }

    public function jsonSerialize(): string|array
    {
        return $this->count() === 1
            ? $this[0]->value
            : array_map(
                fn(SchemaType $schemaType): string => $schemaType->value,
                $this->getArrayCopy()
            );
    }
}
