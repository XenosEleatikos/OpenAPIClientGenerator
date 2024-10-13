<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client1\Api;

/**
 * # pet
 *
 * Everything about your Pets
 *
 * @link http://swagger.io
 */
class PetApi
{
    public function __construct(
        public \Psr\Http\Client\ClientInterface                                               $httpClient,
        public \Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client1\Config\Config $config,
    ) {
    }

    /**
     * Update an existing pet
     *
     * Update an existing pet by Id
     */
    public function updatePet(
    ): \PetstoreClient\Response\UpdatePet200Response|\PetstoreClient\Response\UpdatePet400Response|\PetstoreClient\Response\UpdatePet404Response|\PetstoreClient\Response\UpdatePet422Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'PUT',
                uri: '/pet'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\UpdatePet200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\UpdatePet400Response::make('400'),
            404 => \PetstoreClient\Response\UpdatePet404Response::make('404'),
            422 => \PetstoreClient\Response\UpdatePet422Response::make('422'),
        };
    }

    /**
     * Add a new pet to the store
     *
     * Add a new pet to the store
     */
    public function addPet(
    ): \PetstoreClient\Response\AddPet200Response|\PetstoreClient\Response\AddPet400Response|\PetstoreClient\Response\AddPet422Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: '/pet'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\AddPet200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\AddPet400Response::make('400'),
            422 => \PetstoreClient\Response\AddPet422Response::make('422'),
        };
    }

    /**
     * Finds Pets by status
     *
     * Multiple status values can be provided with comma separated strings
     */
    public function findPetsByStatus(
    ): \PetstoreClient\Response\FindPetsByStatus200Response|\PetstoreClient\Response\FindPetsByStatus400Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/pet/findByStatus'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\FindPetsByStatus200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\FindPetsByStatus400Response::make('400'),
        };
    }

    /**
     * Finds Pets by tags
     *
     * Multiple tags can be provided with comma separated strings. Use tag1, tag2, tag3 for testing.
     */
    public function findPetsByTags(
    ): \PetstoreClient\Response\FindPetsByTags200Response|\PetstoreClient\Response\FindPetsByTags400Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: '/pet/findByTags'
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\FindPetsByTags200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\FindPetsByTags400Response::make('400'),
        };
    }

    /**
     * Find pet by ID
     *
     * Returns a single pet
     */
    public function getPetById(
        string $petId,
    ): \PetstoreClient\Response\GetPetById200Response|\PetstoreClient\Response\GetPetById400Response|\PetstoreClient\Response\GetPetById404Response {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'GET',
                uri: "/pet/$petId"
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\GetPetById200Response::make('200', json_decode($result->getBody()->getContents())),
            400 => \PetstoreClient\Response\GetPetById400Response::make('400'),
            404 => \PetstoreClient\Response\GetPetById404Response::make('404'),
        };
    }

    /**
     * Updates a pet in the store with form data
     */
    public function updatePetWithForm(string $petId): \PetstoreClient\Response\UpdatePetWithForm400Response
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: "/pet/$petId"
            )
        );

        return match ($result->getStatusCode()) {
            400 => \PetstoreClient\Response\UpdatePetWithForm400Response::make('400'),
        };
    }

    /**
     * Deletes a pet
     *
     * delete a pet
     */
    public function deletePet(string $petId): \PetstoreClient\Response\DeletePet400Response
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'DELETE',
                uri: "/pet/$petId"
            )
        );

        return match ($result->getStatusCode()) {
            400 => \PetstoreClient\Response\DeletePet400Response::make('400'),
        };
    }

    /**
     * uploads an image
     */
    public function uploadFile(string $petId): \PetstoreClient\Response\UploadFile200Response
    {
        $result = $this->httpClient->sendRequest(
            new  \GuzzleHttp\Psr7\Request(
                method: 'POST',
                uri: "/pet/$petId/uploadImage"
            )
        );

        return match ($result->getStatusCode()) {
            200 => \PetstoreClient\Response\UploadFile200Response::make('200', json_decode($result->getBody()->getContents())),
        };
    }
}
