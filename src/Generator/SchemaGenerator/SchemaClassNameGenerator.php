<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\OpenAPI;

use function array_pop;
use function explode;
use function ucfirst;

class SchemaClassNameGenerator
{
    /**
     * @return array{0: string, 1: Schema}
     */
    public function createSchemaClassName(
        Schema|Reference $schemaOrReference,
        OpenAPI $openAPI,
        string $parentClassName,
        string $propertyName
    ): array {
        if ($schemaOrReference instanceof Reference) {
            $propertyClassName = $this->createSchemaClassNameFromReferencePath($schemaOrReference->ref);

            $schemaOrReference = $openAPI->resolveReference($schemaOrReference);
            /** @var Schema $schemaOrReference */
        } else {
            $propertyClassName = $this->createSchemaClassNameFromParentClassNameAndProperty(
                $parentClassName,
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

    public function createSchemaClassNameFromParentClassNameAndProperty(string $parentClassName, string $propertyName): string
    {
        return $parentClassName . ucfirst($propertyName);
    }
}
