<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Api;

/**
 * # Test8
 */
class Test8Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Config\Config $config,
    ) {
    }

    public function patchPet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'PATCH',
                uri: '/pet'
            )
        );
    }
}
