<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\Schemas;
use Xenos\OpenApiClientGenerator\Generator\AbstractGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function array_merge;

readonly class SchemaGenerator extends AbstractGenerator
{
    private SchemaClassNameGenerator $schemaClassNameGenerator;
    private SchemaGeneratorContainer $schemaGeneratorContainer;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->schemaGeneratorContainer = new SchemaGeneratorContainer($config, $printer);
        $this->schemaClassNameGenerator = new SchemaClassNameGenerator();
    }

    public function generate(OpenAPI $openAPI): void
    {
        foreach ($this->findAllSchemas($openAPI) as $name => $schema) {
            $schemaGenerator = $this->schemaGeneratorContainer->getSchemaGenerator($schema);
            $schemaGenerator?->generateSchema($name, $schema, $openAPI);
        }
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
