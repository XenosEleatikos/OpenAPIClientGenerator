<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Generator;

readonly class Generator extends AbstractGenerator
{
    public function generate(): void
    {
        $schemaGenerator = new SchemaGenerator($this->config, $this->printer);
        $schemaGenerator->generate($this->config->openAPI);

        $responseGenerator = new ResponseGenerator($this->config, $this->printer);
        $responseGenerator->generate($this->config->openAPI);

        $clientGenerator = new ClientGenerator($this->config, $this->printer);
        $clientGenerator->generate($this->config->openAPI);
    }
}
