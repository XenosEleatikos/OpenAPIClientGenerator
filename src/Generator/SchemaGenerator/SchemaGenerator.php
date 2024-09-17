<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\Schemas;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\AbstractGenerator;
use Xenos\OpenApiClientGenerator\Generator\ClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function array_merge;
use function is_null;

readonly class SchemaGenerator extends AbstractGenerator
{
    private ClassGenerator $classGenerator;
    private EnumGenerator $enumGenerator;
    private SchemaClassNameGenerator $schemaClassNameGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->classGenerator = new ClassGenerator($config, $printer);
        $this->enumGenerator = new EnumGenerator($config, $printer);
        $this->schemaClassNameGenerator = new SchemaClassNameGenerator();
    }

    public function generate(OpenAPI $openAPI): void
    {
        foreach ($this->findAllSchemas($openAPI) as $name => $schema) {
            if (self::getPhpType($schema) === 'enum' || self::getPhpType($schema) === 'object') {
                $this->generateSchema($name, $schema, $openAPI);
            }
        }
    }

    public static function getPhpType(?Schema $schema): string
    {
        if (is_null($schema)) {
            return 'mixed';
        }

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

    /** @return array<string, \Xenos\OpenApi\Model\Schema> */
    private function findAnonymousSchemas(OpenAPI $openAPI): array
    {
        foreach ($openAPI->components->schemas as $referencePath => $schema) {
            $anonymousSchemas = array_merge(
                $anonymousSchemas ?? [],
                $this->findAnonymousSchemasRecursive(
                    $this->schemaClassNameGenerator->createSchemaClassNameFromReferencePath($referencePath),
                    $schema
                )
            );
        }

        return $anonymousSchemas ?? [];
    }

    /** @return Schema[] */
    private function findAnonymousSchemasRecursive(string $parentClassName, Schema $schema): array
    {
        foreach ($schema->properties as $propertyName => $schemaOrReference) {
            if ($schemaOrReference instanceof Schema) {
                $schemaClassName = $this->schemaClassNameGenerator->createSchemaClassNameFromParentClassNameAndProperty($parentClassName, $propertyName);
                $anonymousSchemas[$schemaClassName] = $schemaOrReference;
                $anonymousSchemas = array_merge(
                    $anonymousSchemas,
                    $this->findAnonymousSchemasRecursive($schemaClassName, $schemaOrReference)
                );
            }
        }

        return $anonymousSchemas ?? [];
    }

    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void
    {
        if ($schema->type->contains(SchemaType::OBJECT)) {
            $this->classGenerator->generateSchema($name, $schema, $openAPI);
        }
        if ($schema->isEnum()) {
            $this->enumGenerator->generateSchema($name, $schema);
        }
    }

    private function findAllSchemas(OpenAPI $openAPI): Schemas
    {
        return new Schemas(
            array_merge(
                (array)$openAPI->components->schemas,
                $this->findAnonymousSchemas($openAPI)
            )
        );
    }
}
