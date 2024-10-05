<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;
use Xenos\OpenApi\Model\OpenAPI;

readonly class Generator extends AbstractGenerator
{
    public function generate(OpenAPI $openAPI): void
    {
        $schemaGenerator = new SchemaGenerator($this->config, $this->printer);
        $schemaGenerator->generate($openAPI);

        $responseGenerator = new ResponseGenerator($this->config, $this->printer);
        $responseGenerator->generate($openAPI);

        $clientGenerator = new ClientGenerator($this->config, $this->printer);
        $clientGenerator->generate($openAPI);

        $configGenerator = new ConfigGenerator($this->config, $this->printer);
        $configGenerator->generate($openAPI);
    }
}
