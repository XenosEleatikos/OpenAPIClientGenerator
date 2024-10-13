<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client2;

/**
 * # Pet Shop API
 *
 * Version: 1.0.0
 */
class Client
{
    public function __construct(
        private \Psr\Http\Client\ClientInterface $httpClient,
        private Config\Config $config,
    ) {
    }
}
