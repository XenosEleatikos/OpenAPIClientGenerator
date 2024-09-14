<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Generator;

use OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;

readonly class Generator extends AbstractGenerator
{
    public function generate(OpenAPI $openApi): void
    {
        $schemaGenerator = new SchemaGenerator($this->config, $this->printer);
        $schemaGenerator->generate($openApi);

        $responseGenerator = new ResponseGenerator($this->config, $this->printer);
        $responseGenerator->generate($openApi);

        $clientGenerator = new ClientGenerator($this->config, $this->printer);
        $clientGenerator->generate($openApi);
    }
}
