<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Config;

class Config
{
    public function __construct(
        public string $namespace,
        public string $directory,
    ) {
    }
}
