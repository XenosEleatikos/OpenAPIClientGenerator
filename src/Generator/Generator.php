<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator\ClientGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;

readonly class Generator
{
    public function __construct(
        private SchemaGenerator $schemaGenerator,
        private ResponseGenerator $responseGenerator,
        private ClientGenerator $clientGenerator,
        private ConfigGenerator $configGenerator,
    ) {
    }

    public function generate(OpenAPI $openAPI): void
    {
        $this->schemaGenerator->generate($openAPI);
        $this->responseGenerator->generate($openAPI);
        $this->clientGenerator->generate($openAPI);
        $this->configGenerator->generate($openAPI);
    }
}
