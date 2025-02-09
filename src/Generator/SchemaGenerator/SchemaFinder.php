<?php

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\Schemas;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApi\Model\SchemaTypes;
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
                $this->findSchemasInComponents($openAPI),
                $this->findSchemasInResponses($openAPI),
            )
        );
    }

    /** @return array<string, Schema> */
    private function findSchemasInComponents(OpenAPI $openAPI): array
    {
        foreach ($openAPI->components->schemas as $referencePath => $schema) {
            $schemas = array_merge(
                $schemas ?? [],
                $this->findSchemasRecursive(
                    parentClassName: $this->schemaClassNameGenerator->createSchemaClassNameFromReferencePath($referencePath),
                    schema: $schema
                )
            );
        }

        return $schemas ?? [];
    }

    /** @return array<string, Schema> */
    private function findSchemasInResponses(OpenAPI $openAPI): array
    {
        foreach ($this->responseFinder->findResponses($openAPI) as $fqcn => $response) {
            /** @todo Implement other media types */
            if (isset($response->content['application/json'])) {
                /** @var MediaType $jsonMediaType */
                $jsonMediaType = $response->content['application/json'];
                if ($jsonMediaType->schema instanceof Schema) {
                    $schemas = array_merge(
                        $schemas ?? [],
                        $this->findSchemasRecursive(
                            parentClassName: (new FullyQualifiedClassName($fqcn))->getClassName() . 'JsonSchema',
                            schema: $jsonMediaType->schema
                        )
                    );
                }
            }
        }

        return $schemas ?? [];
    }

    /** @return Schema[] */
    private function findSchemasRecursive(string $parentClassName, Schema $schema): array
    {
        $schemas[$parentClassName] = $schema;

        foreach ($schema->properties as $propertyName => $schemaOrReference) {
            if ($schemaOrReference instanceof Schema) {
                $schemaClassName = $this->schemaClassNameGenerator
                    ->createSchemaClassNameFromParentClassNameAndProperty(
                        parentClassName: $parentClassName,
                        propertyName: $propertyName
                    );
                $schemas = array_merge(
                    $schemas,
                    $this->findSchemasRecursive($schemaClassName, $schemaOrReference)
                );
            }
        }

        if ($schema->type->contains(SchemaType::OBJECT)) {
            if ($schema->additionalProperties !== false) {
                $schemaClassName = $this->schemaClassNameGenerator
                    ->createSchemaClassNameFromParentClassNameAndProperty(
                        parentClassName: $parentClassName,
                        propertyName: 'additionalProperties'
                    );
                $schemas[$schemaClassName] = new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: $schema->additionalProperties === true
                        ? null
                        : $schema->additionalProperties,
                );
            }

            if ($schema->additionalProperties instanceof Schema) {
                $schemaClassName = $this->schemaClassNameGenerator
                    ->createSchemaClassNameFromParentClassNameAndProperty(
                        parentClassName: $parentClassName,
                        propertyName: 'additionalProperty'
                    );
                $schemas = array_merge(
                    $schemas,
                    $this->findSchemasRecursive($schemaClassName, $schema->additionalProperties)
                );
            }
        }

        if ($schema->items instanceof Schema) {
            $schemaClassName = $this->schemaClassNameGenerator
                ->createSchemaClassNameFromParentClassNameAndProperty(
                    parentClassName: $parentClassName,
                    propertyName: 'item'
                );
            $schemas = array_merge(
                $schemas,
                $this->findSchemasRecursive($schemaClassName, $schema->items)
            );
        }

        return $schemas;
    }
}
