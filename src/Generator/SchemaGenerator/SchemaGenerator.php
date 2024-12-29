<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schemas;

readonly class SchemaGenerator
{
    public function __construct(
        private SchemaGeneratorContainer $schemaGeneratorContainer,
    ) {
    }

    public function generate(Schemas $schemas, OpenAPI $openAPI): void
    {
        foreach ($schemas as $name => $schema) {
            $schemaGenerator = $this->schemaGeneratorContainer->getSchemaGenerator($schema);
            $schemaGenerator?->generateSchema($name, $schema, $openAPI);
        }
    }
}
