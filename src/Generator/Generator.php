<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator\ClientGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseFinder;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaFinder;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;

readonly class Generator
{
    public function __construct(
        private SchemaGenerator $schemaGenerator,
        private ResponseGenerator $responseGenerator,
        private ClientGenerator $clientGenerator,
        private ConfigGenerator $configGenerator,
        private ResponseFinder $responseFinder,
        private SchemaFinder $schemaFinder
    ) {
    }

    public function generate(OpenAPI $openAPI): void
    {
        $this->schemaGenerator->generate(
            schemas: $this->schemaFinder->findAllSchemas($openAPI),
            openAPI: $openAPI
        );
        $this->responseGenerator->generate(
            responses: $this->responseFinder->findResponses($openAPI),
            openAPI: $openAPI
        );
        $this->clientGenerator->generate($openAPI);
        $this->configGenerator->generate($openAPI);
    }
}
