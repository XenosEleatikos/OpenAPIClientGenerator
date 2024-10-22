<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateClassComment\Api;

/**
 * # Test5
 *
 * @link https://example.com/docs
 */
class Test5Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\Client\Config\Config $config,
    ) {
    }
}
