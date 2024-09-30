<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture;

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

    public function pet(): Api\PetApi
    {
        static $petApi = null;
        return $petApi ??= new Api\PetApi($this->httpClient, $this->config);
    }

    public function store(): Api\StoreApi
    {
        static $storeApi = null;
        return $storeApi ??= new Api\StoreApi($this->httpClient, $this->config);
    }

    public function user(): Api\UserApi
    {
        static $userApi = null;
        return $userApi ??= new Api\UserApi($this->httpClient, $this->config);
    }
}
