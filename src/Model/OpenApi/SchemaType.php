<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;

enum SchemaType: string implements JsonSerializable
{
    case OBJECT = 'object';
    case ARRAY = 'array';
    case NUMBER = 'number';
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case NULL = 'null';

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function equals(SchemaType $schemaType): bool
    {
        return $this->value === $schemaType->value;
    }
}
