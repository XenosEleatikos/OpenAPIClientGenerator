<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Config;

use OpenApiClientGenerator\Model\OpenApi\OpenAPI;

class Config
{
    public function __construct(
        public OpenAPI $openAPI,
        public string $namespace,
        public string $directory,
    ) {
    }
}
