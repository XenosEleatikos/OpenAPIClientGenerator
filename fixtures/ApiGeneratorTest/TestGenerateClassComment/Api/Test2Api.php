<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateClassComment\Api;

/**
 * # Test2
 *
 * Some description
 */
class Test2Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\Client\Config\Config $config,
    ) {
    }
}
