<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Client1\Api;

/**
 * # store
 *
 * Access to Petstore orders
 *
 * @link http://swagger.io
 */
class StoreApi
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\Client1\Config\Config $config,
    ) {
    }

    /**
     * Returns pet inventories by status
     *
     * Returns a map of status codes to quantities
     */
    public function getInventory(): \PetstoreClient\Response\GetInventory200Response
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/store/inventory'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\GetInventory200Response::make('200', json_decode($result->getBody()->getContents())),
        };
    }

    /**
     * Place an order for a pet
     *
     * Place a new order in the store
     */
    public function placeOrder(
    ): \PetstoreClient\Response\PlaceOrder200Response|\PetstoreClient\Response\PlaceOrder400Response|\PetstoreClient\Response\PlaceOrder422Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: '/store/order'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\PlaceOrder200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\PlaceOrder400Response::make('400'),
            422 => \PetstoreClient\Response\PlaceOrder422Response::make('422'),
        };
    }

    /**
     * Find purchase order by ID
     *
     * For valid response try integer IDs with value <= 5 or > 10. Other values will generate exceptions.
     */
    public function getOrderById(
        string $orderId,
    ): \PetstoreClient\Response\GetOrderById200Response|\PetstoreClient\Response\GetOrderById400Response|\PetstoreClient\Response\GetOrderById404Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: "/store/order/$orderId"
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\GetOrderById200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\GetOrderById400Response::make('400'),
            404 => \PetstoreClient\Response\GetOrderById404Response::make('404'),
        };
    }

    /**
     * Delete purchase order by ID
     *
     * For valid response try integer IDs with value < 1000. Anything above 1000 or nonintegers will generate API errors
     */
    public function deleteOrder(
        string $orderId,
    ): \PetstoreClient\Response\DeleteOrder400Response|\PetstoreClient\Response\DeleteOrder404Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'DELETE',
                uri: "/store/order/$orderId"
            )
        );

        return match ($result->getStatusCode()) {
            400 => \PetstoreClient\Response\DeleteOrder400Response::make('400'),
            404 => \PetstoreClient\Response\DeleteOrder404Response::make('404'),
        };
    }
}
