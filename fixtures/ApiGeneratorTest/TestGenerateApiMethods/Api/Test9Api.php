<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Api;

/**
 * # Test9
 */
class Test9Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Config\Config $config,
    ) {
    }

    public function tracePet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'TRACE',
                uri: '/pet'
            )
        );
    }
}
