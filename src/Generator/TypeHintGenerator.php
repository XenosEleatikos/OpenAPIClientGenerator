<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Generator;

use OpenApiClientGenerator\Model\OpenApi\Schema;
use OpenApiClientGenerator\Model\OpenApi\SchemaType;

readonly class TypeHintGenerator extends AbstractGenerator
{
    /** @return string[] */
    public function getReturnTypes(Schema $schema, string $name = null): array
    {
        foreach ($schema->type as $schemaType) {
            $typeHints[] = match ($schemaType) {
                SchemaType::OBJECT => isset($name) ? $this->config->namespace . '\Schema\\' . $name : 'object',
                SchemaType::ARRAY => 'array',
                SchemaType::NUMBER => 'float',
                SchemaType::INTEGER => 'int',
                SchemaType::STRING => 'string',
                SchemaType::BOOLEAN => 'bool',
                SchemaType::NULL => 'null',
            };
        }

        return $typeHints ?? [];
    }
}
