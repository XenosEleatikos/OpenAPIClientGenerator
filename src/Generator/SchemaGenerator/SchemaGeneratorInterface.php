<?php

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;

interface SchemaGeneratorInterface
{
    public function isResponsible(Schema $schema): bool;
    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void;
    public function getFactoryCall(string $propertyClassName, string $propertyName): string;
}
