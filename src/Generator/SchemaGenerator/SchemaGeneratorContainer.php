<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use stdClass;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function is_null;

class SchemaGeneratorContainer
{
    public function __construct(
        private readonly Config $config,
        private readonly SchemaClassNameGenerator $schemaClassNameGenerator,
        public ClassGenerator $classGenerator,
        public EnumGenerator $enumGenerator,
        public EnumClassGenerator $enumClassGenerator,
    ) {
        $this->classGenerator->setContainer($this);

        $this->generators[EnumGenerator::class] = $this->enumGenerator;
        $this->generators[EnumClassGenerator::class] = $this->enumClassGenerator;
        $this->generators[ClassGenerator::class] = $this->classGenerator;
    }

    /** @var SchemaGeneratorInterface[] */
    private array $generators = [];

    public function getSchemaGenerator(Schema $schema): ?SchemaGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->isResponsible($schema)) {
                return $generator;
            }
        }

        return null;
    }

    public function getFactoryCall(
        null|Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
        string $parentClassName,
        string $propertyName,
        string $parameter,
    ): string {
        /** @var Schema $schema */
        $schema = $schemaOrReference instanceof Reference
            ? $openAPI->resolveReference($schemaOrReference)
            : $schemaOrReference;

        $schemaGenerator = $this->getSchemaGenerator($schema);

        $returnTypes = $this->getReturnTypes(
            schemaOrReference: $schemaOrReference,
            openAPI: $openAPI,
            parentClassName: $parentClassName,
            propertyName: $propertyName
        );

        return isset($schemaGenerator)
            ? $schemaGenerator->getFactoryCall(
                propertyClassName: '\\' . $returnTypes[0],
                parameter: $parameter,
            )
            : $parameter;
    }
    /** @return array<int, string|FullyQualifiedClassName> */
    public function getReturnTypes(
        null|Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
        string $parentClassName,
        string $propertyName
    ): array {
        if (is_null($schemaOrReference)) {
            return ['mixed'];
        }

        [$className, $schema] = $this->schemaClassNameGenerator
            ->createSchemaClassName(
                schemaOrReference: $schemaOrReference,
                openAPI: $openAPI,
                parentClassName: $parentClassName,
                propertyName: $propertyName
            );

        if ($schema->isEnumOfStrings() || $schema->isEnumOfIntegers()) {
            return [new FullyQualifiedClassName($this->config->namespace . '\Schema\\' . $className)];
        }

        foreach ($schema->type as $schemaType) {
            $typeHints[] = match ($schemaType) {
                SchemaType::OBJECT => new FullyQualifiedClassName($this->config->namespace . '\Schema\\' . $className),
                SchemaType::ARRAY => 'array',
                SchemaType::NUMBER => 'int|float',
                SchemaType::INTEGER => 'int',
                SchemaType::STRING => 'string',
                SchemaType::BOOLEAN => 'bool',
                SchemaType::NULL => 'null',
            };
        }

        return $typeHints ?? [];
    }

    /** @return string[] */
    public function getRawDataTypes(
        null|Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
    ): array {
        if (is_null($schemaOrReference)) {
            return ['mixed'];
        }

        /** @var Schema $schema */
        $schema = $schemaOrReference instanceof Reference
            ? $openAPI->resolveReference($schemaOrReference)
            : $schemaOrReference;

        if ($schema->isEnumOfStrings()) {
            return ['string'];
        }
        if ($schema->isEnumOfIntegers()) {
            return ['int'];
        }

        foreach ($schema->type as $schemaType) {
            $typeHints[] = match ($schemaType) {
                SchemaType::OBJECT => stdClass::class,
                SchemaType::ARRAY => 'array',
                SchemaType::NUMBER => 'int|float',
                SchemaType::INTEGER => 'int',
                SchemaType::STRING => 'string',
                SchemaType::BOOLEAN => 'bool',
                SchemaType::NULL => 'null',
            };
        }

        return $typeHints ?? [];
    }
}
