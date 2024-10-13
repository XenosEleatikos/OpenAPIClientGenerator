<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client1\Api;

/**
 * # user
 *
 * Operations about user
 */
class UserApi
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface                                               $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client1\Config\Config $config,
    ) {
    }

    /**
     * Create user
     *
     * This can only be done by the logged in user.
     */
    public function createUser(): \PetstoreClient\Response\CreateUserdefaultResponse
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: '/user'
            )
        );

        return match ($result->getStatusCode()) {
            default => \PetstoreClient\Response\CreateUserdefaultResponse::make('default', json_decode($result->getBody()->getContents())),
        };
    }

    /**
     * Creates list of users with given input array
     *
     * Creates list of users with given input array
     */
    public function createUsersWithListInput(
    ): \PetstoreClient\Response\CreateUsersWithListInput200Response|\PetstoreClient\Response\CreateUsersWithListInputdefaultResponse {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: '/user/createWithList'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\CreateUsersWithListInput200Response::make('200', json_decode($result->getBody()->getContents())),
            default => \PetstoreClient\Response\CreateUsersWithListInputdefaultResponse::make('default'),
        };
    }

    /**
     * Logs user into the system
     */
    public function loginUser(
    ): \PetstoreClient\Response\LoginUser200Response|\PetstoreClient\Response\LoginUser400Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/user/login'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\LoginUser200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\LoginUser400Response::make('400'),
        };
    }

    /**
     * Logs out current logged in user session
     */
    public function logoutUser(): \PetstoreClient\Response\LogoutUserdefaultResponse
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/user/logout'
            )
        );

        return match ($result->getStatusCode()) {
            default => \PetstoreClient\Response\LogoutUserdefaultResponse::make('default'),
        };
    }

    /**
     * Get user by user name
     */
    public function getUserByName(
        string $username,
    ): \PetstoreClient\Response\GetUserByName200Response|\PetstoreClient\Response\GetUserByName400Response|\PetstoreClient\Response\GetUserByName404Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: "/user/$username"
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\GetUserByName200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\GetUserByName400Response::make('400'),
            404 => \PetstoreClient\Response\GetUserByName404Response::make('404'),
        };
    }

    /**
     * Update user
     *
     * This can only be done by the logged in user.
     */
    public function updateUser(string $username): \PetstoreClient\Response\UpdateUserdefaultResponse
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'PUT',
                uri: "/user/$username"
            )
        );

        return match ($result->getStatusCode()) {
            default => \PetstoreClient\Response\UpdateUserdefaultResponse::make('default'),
        };
    }

    /**
     * Delete user
     *
     * This can only be done by the logged in user.
     */
    public function deleteUser(
        string $username,
    ): \PetstoreClient\Response\DeleteUser400Response|\PetstoreClient\Response\DeleteUser404Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'DELETE',
                uri: "/user/$username"
            )
        );

        return match ($result->getStatusCode()) {
            400 => \PetstoreClient\Response\DeleteUser400Response::make('400'),
            404 => \PetstoreClient\Response\DeleteUser404Response::make('404'),
        };
    }
}
