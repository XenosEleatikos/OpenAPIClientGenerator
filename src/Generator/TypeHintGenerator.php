<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Generator;

use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Reference;
use OpenApiClientGenerator\Model\OpenApi\Schema;
use OpenApiClientGenerator\Model\OpenApi\SchemaType;
use function ucfirst;

readonly class TypeHintGenerator extends AbstractGenerator
{
    /** @return string[] */
    public function getReturnTypes(
        Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
        string $parentClassName,
        string $propertyName
    ): array {
        list($className, $schema) = SchemaGenerator::createSchemaClassName($schemaOrReference, $openAPI, $parentClassName, $propertyName);

        if ($schema->isEnumOfStrings() || $schema->isEnumOfIntegers()) {
            return [$this->config->namespace . '\Schema\\' . $className];
        }

        foreach ($schema->type as $schemaType) {
            $typeHints[] = match ($schemaType) {
                SchemaType::OBJECT => $this->config->namespace . '\Schema\\' . $className,
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
