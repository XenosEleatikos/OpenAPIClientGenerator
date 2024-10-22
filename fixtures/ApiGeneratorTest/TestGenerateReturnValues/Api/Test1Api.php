<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateReturnValues\Api;

/**
 * # Test1
 */
class Test1Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateReturnValues\Config\Config $config,
    ) {
    }

    public function getPet(
    ): \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateReturnValues\Response\GetPet200Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/pet'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateReturnValues\Response\GetPet200Response::make('200', json_decode($result->getBody()->getContents())),
        };
    }
}
