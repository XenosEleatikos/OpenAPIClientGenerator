<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApi\Model\OpenAPI;

use function is_null;

readonly class TypeHintGenerator
{
    public function __construct(
        private Config $config,
        private SchemaClassNameGenerator $schemaClassNameGenerator
    ) {
    }

    /** @return string[] */
    public function getReturnTypes(
        null|Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
        string $parentClassName,
        string $propertyName
    ): array {
        if (is_null($schemaOrReference)) {
            return ['mixed'];
        }

        list($className, $schema) = $this->schemaClassNameGenerator->createSchemaClassName($schemaOrReference, $openAPI, $parentClassName, $propertyName);

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
