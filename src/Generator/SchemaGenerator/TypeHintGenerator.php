<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Generator;

use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Reference;
use OpenApiClientGenerator\Model\OpenApi\Schema;
use OpenApiClientGenerator\Model\OpenApi\SchemaType;
use OpenApiClientGenerator\Printer\Printer;

use function is_null;

readonly class TypeHintGenerator extends AbstractGenerator
{
    private SchemaClassNameGenerator $schemaClassNameGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->schemaClassNameGenerator = new SchemaClassNameGenerator();

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
