<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Api;

/**
 * # Test10
 */
class Test10Api
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ApiGeneratorTest\TestGenerateApiMethods\Config\Config $config,
    ) {
    }

    public function getPet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/pet'
            )
        );
    }

    public function putPet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'PUT',
                uri: '/pet'
            )
        );
    }

    public function postPet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: '/pet'
            )
        );
    }

    public function deletePet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'DELETE',
                uri: '/pet'
            )
        );
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

    public function headPet(): void
    {
        $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'HEAD',
                uri: '/pet'
            )
        );
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
