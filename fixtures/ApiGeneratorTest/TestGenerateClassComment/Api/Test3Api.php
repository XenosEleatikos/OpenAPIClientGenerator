<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateClassComment\Api;

/**
 * # Test3
 *
 * Some description
 *
 * @link https://example.com/docs
 */
class Test3Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\Client\Config\Config $config,
    ) {
    }
}
