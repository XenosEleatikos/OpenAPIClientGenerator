<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Client1\Config;

class Config
{
    public function __construct(
        public Server $server,
    ) {
    }
}
