<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Client5;

/**
 * # Pet Shop API
 *
 * Version: 1.0.0
 *
 * ## License
 *
 * MIT License
 */
class Client
{
    public function __construct(
        private \Psr\Http\Client\ClientInterface $httpClient,
        private Config\Config $config,
    ) {
    }
}
