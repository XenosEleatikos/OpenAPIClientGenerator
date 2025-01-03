<?php

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\Schemas;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseFinder;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function array_merge;

readonly class SchemaFinder
{
    public function __construct(
        private SchemaClassNameGenerator $schemaClassNameGenerator,
        private ResponseFinder $responseFinder,
    ) {
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

    /** @return array<string, Schema> */
    private function findAnonymousSchemas(OpenAPI $openAPI): array
    {
        foreach ($openAPI->components->schemas as $referencePath => $schema) {
            $anonymousSchemas = array_merge(
                $anonymousSchemas ?? [],
                $this->findAnonymousSchemasRecursive(
                    parentClassName: $this->schemaClassNameGenerator->createSchemaClassNameFromReferencePath($referencePath),
                    schema: $schema
                )
            );
        }

        foreach ($this->responseFinder->findResponses($openAPI) as $fqcn => $response) {
            /** @todo Implement other media types */
            if (isset($response->content['application/json'])) {
                /** @var MediaType $jsonMediaType */
                $jsonMediaType = $response->content['application/json'];
                if ($jsonMediaType->schema instanceof Schema) {
                    $anonymousSchemas = array_merge(
                        $anonymousSchemas ?? [],
                        $this->findAnonymousSchemasRecursive(
                            parentClassName: (new FullyQualifiedClassName($fqcn))->getClassName() . 'JsonSchema',
                            schema: $jsonMediaType->schema
                        )
                    );
                }
            }
        }

        return $anonymousSchemas ?? [];
    }

    /** @return Schema[] */
    private function findAnonymousSchemasRecursive(string $parentClassName, Schema $schema): array
    {
        $anonymousSchemas[$parentClassName] = $schema;
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

        return $anonymousSchemas;
    }
}
