<?php

namespace OpenApiClientGenerator\Generator;

use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use OpenApiClientGenerator\Generator\SchemaGenerator\ClassGenerator;
use OpenApiClientGenerator\Model\OpenApi\Reference;
use OpenApiClientGenerator\Model\OpenApi\Response;
use OpenApiClientGenerator\Model\OpenApi\Schemas;
use OpenApiClientGenerator\Model\OpenApi\SchemaType;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Schema;
use function array_merge;

readonly class SchemaGenerator extends AbstractGenerator
{
    private ClassGenerator $classGenerator;
    private EnumGenerator $enumGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->classGenerator = new ClassGenerator($config, $printer);
        $this->enumGenerator = new EnumGenerator($config, $printer);
    }

    public function generate(OpenAPI $openAPI): void
    {
        foreach ($this->findAllSchemas($openAPI) as $name => $schema) {
            if (self::getPhpType($schema) === 'enum' || self::getPhpType($schema) === 'object') {
                $this->generateSchema($name, $schema, $openAPI);
            }
        }
    }

    public static function getPhpType(Schema $schema): string
    {
        if (
            $schema->isEnumOfStrings()
            || $schema->isEnumOfIntegers()
        ) {
            return 'enum';
        }

        if ($schema->type->contains(SchemaType::OBJECT)) {
            return 'object';
        }

        return 'scalar';
    }

    /** @return array<string, Schema> */
    private function findAnonymousSchemas(OpenAPI $openAPI): array
    {
        foreach ($openAPI->components->schemas as $referencePath => $schema) {
            $anonymousSchemas = array_merge(
                $anonymousSchemas ?? [],
                $this->findAnonymousSchemasRecursive(
                    self::createSchemaClassNameFromReferencePath($referencePath),
                    $schema
                )
            );
        }

        return $anonymousSchemas ?? [];
    }

    private function findAnonymousSchemasRecursive(string $parentClassName, Schema $schema): array
    {
        foreach ($schema->properties as $propertyName => $schemaOrReference) {
            if ($schemaOrReference instanceof Schema) {
                $schemaClassName = self::createSchemaClassNameFromParentClassNameAndProperty($parentClassName, $propertyName);
                $anonymousSchemas[$schemaClassName] = $schemaOrReference;
                $anonymousSchemas = array_merge(
                    $anonymousSchemas,
                    $this->findAnonymousSchemasRecursive($schemaClassName, $schemaOrReference)
                );
            }
        }

        return $anonymousSchemas ?? [];
    }

    /**
     * @return array{0: string, 1: Schema}
     */
    public static function createSchemaClassName(
        Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
        string $className,
        string $propertyName
    ): array {
        if ($schemaOrReference instanceof Reference) {
            $propertyClassName = SchemaGenerator::createSchemaClassNameFromReferencePath($schemaOrReference->ref);

            $schemaOrReference = $openAPI->resolveReference($schemaOrReference);
            /** @var Schema $schemaOrReference */
        } else {
            $propertyClassName = SchemaGenerator::createSchemaClassNameFromParentClassNameAndProperty(
                $className,
                $propertyName
            );
        }

        return [
            $propertyClassName,
            $schemaOrReference
        ];
    }

    public static function createSchemaClassNameFromReferencePath(string $referencePath): string
    {
        $referencePath = explode('/', $referencePath);

        return array_pop($referencePath);
    }

    public static function createSchemaClassNameFromParentClassNameAndProperty(string $parentClassName, string $propertyName): string
    {
        return $parentClassName . ucfirst($propertyName);
    }

    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void
    {
        if ($schema->type->contains(SchemaType::OBJECT)) {
            $this->classGenerator->generateSchema($name, $schema, $openAPI);
        }
        if ($schema->isEnum()) {
            $this->enumGenerator->generateSchema($name, $schema, $openAPI);
        }
    }

    public function findAllSchemas(OpenAPI $openAPI): Schemas
    {
        return new Schemas(
            array_merge(
                (array)$openAPI->components->schemas,
                $this->findAnonymousSchemas($openAPI)
            )
        );
    }
}
