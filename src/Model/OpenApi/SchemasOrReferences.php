<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_filter;
use function array_map;

/** @extends ArrayObject<string, Schema|Reference> */
class SchemasOrReferences extends ArrayObject implements JsonSerializable
{
    public static function make(stdClass $schemas): self
    {
        $instance = new self();

        foreach ((array)$schemas as $name => $schemaOrReference) {
            $instance[$name] = Schema::makeSchemaOrReference($schemaOrReference);
        }

        return $instance;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter(
            array_map(
                fn (Schema|Reference $schema) => $schema->jsonSerialize(),
                $this->getArrayCopy()
            )
        );
    }

    public function resolveProperties(OpenAPI $openAPI): Schemas
    {
        foreach ($this as $name => $schemaOrReference) {
            if ($schemaOrReference instanceof Reference) {
                /** @var Schema $schema */
                $schema = $openAPI->resolveReference($schemaOrReference);
                $schemas[$name] = $schema;
            } else {
                $schemas[$name] = $schemaOrReference;
            }
        }

        return new Schemas($schemas ?? []);
    }
}
