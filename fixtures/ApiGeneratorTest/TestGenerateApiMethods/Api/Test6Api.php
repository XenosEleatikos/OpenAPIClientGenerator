<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Api;

/**
 * # Test6
 */
class Test6Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Config\Config $config,
    ) {
    }

    public function optionsPet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'OPTIONS',
                uri: '/pet'
            )
        );
    }
}
