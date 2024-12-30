<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ApiGenerator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Method;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;

class MethodNameGeneratorTest extends TestCase
{
    private MethodNameGenerator $methodNameGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->methodNameGenerator = new MethodNameGenerator();
    }

    #[DataProvider('provideDataForTestGenerateMethodName')]
    public function testGenerateMethodName(
        string $expectedMethodName,
        Method $method,
        string $path,
        Operation $operation
    ): void {
        self::assertSame(
            $expectedMethodName,
            $this->methodNameGenerator->generateMethodName($method, $path, $operation)
        );
    }

    /** @return array<string, array{expectedMethodName: string, method: Method, path: string, operation: Operation}> */
    public static function provideDataForTestGenerateMethodName(): array
    {
        return [
            // GET
            'GET method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::GET,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'GET method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::GET,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'GET method without operation ID' => [
                'expectedMethodName' => 'getPets',
                'method' => Method::GET,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'GET method with parameter' => [
                'expectedMethodName' => 'getPetsByPetId',
                'method' => Method::GET,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'GET method with parameter and subresource' => [
                'expectedMethodName' => 'getPetsByUserIdOrders',
                'method' => Method::GET,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'GET method with two parameters and subresource' => [
                'expectedMethodName' => 'getProductsByCategoryIdByProductIdDetails',
                'method' => Method::GET,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // PUT
            'PUT method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::PUT,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'PUT method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::PUT,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'PUT method without operation ID' => [
                'expectedMethodName' => 'putPets',
                'method' => Method::PUT,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'PUT method with parameter' => [
                'expectedMethodName' => 'putPetsByPetId',
                'method' => Method::PUT,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'PUT method with parameter and subresource' => [
                'expectedMethodName' => 'putPetsByUserIdOrders',
                'method' => Method::PUT,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'PUT method with two parameters and subresource' => [
                'expectedMethodName' => 'putProductsByCategoryIdByProductIdDetails',
                'method' => Method::PUT,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // POST
            'POST method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::POST,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'POST method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::POST,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'POST method without operation ID' => [
                'expectedMethodName' => 'postPets',
                'method' => Method::POST,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'POST method with parameter' => [
                'expectedMethodName' => 'postPetsByPetId',
                'method' => Method::POST,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'POST method with parameter and subresource' => [
                'expectedMethodName' => 'postPetsByUserIdOrders',
                'method' => Method::POST,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'POST method with two parameters and subresource' => [
                'expectedMethodName' => 'postProductsByCategoryIdByProductIdDetails',
                'method' => Method::POST,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // DELETE
            'DELETE method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::DELETE,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'DELETE method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::DELETE,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'DELETE method without operation ID' => [
                'expectedMethodName' => 'deletePets',
                'method' => Method::DELETE,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'DELETE method with parameter' => [
                'expectedMethodName' => 'deletePetsByPetId',
                'method' => Method::DELETE,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'DELETE method with parameter and subresource' => [
                'expectedMethodName' => 'deletePetsByUserIdOrders',
                'method' => Method::DELETE,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'DELETE method with two parameters and subresource' => [
                'expectedMethodName' => 'deleteProductsByCategoryIdByProductIdDetails',
                'method' => Method::DELETE,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // OPTIONS
            'OPTIONS method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::OPTIONS,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'OPTIONS method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::OPTIONS,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'OPTIONS method without operation ID' => [
                'expectedMethodName' => 'optionsPets',
                'method' => Method::OPTIONS,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'OPTIONS method with parameter' => [
                'expectedMethodName' => 'optionsPetsByPetId',
                'method' => Method::OPTIONS,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'OPTIONS method with parameter and subresource' => [
                'expectedMethodName' => 'optionsPetsByUserIdOrders',
                'method' => Method::OPTIONS,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'OPTIONS method with two parameters and subresource' => [
                'expectedMethodName' => 'optionsProductsByCategoryIdByProductIdDetails',
                'method' => Method::OPTIONS,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // HEAD
            'HEAD method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::HEAD,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'HEAD method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::HEAD,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'HEAD method without operation ID' => [
                'expectedMethodName' => 'headPets',
                'method' => Method::HEAD,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'HEAD method with parameter' => [
                'expectedMethodName' => 'headPetsByPetId',
                'method' => Method::HEAD,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'HEAD method with parameter and subresource' => [
                'expectedMethodName' => 'headPetsByUserIdOrders',
                'method' => Method::HEAD,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'HEAD method with two parameters and subresource' => [
                'expectedMethodName' => 'headProductsByCategoryIdByProductIdDetails',
                'method' => Method::HEAD,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // PATCH
            'PATCH method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::PATCH,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'PATCH method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::PATCH,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'PATCH method without operation ID' => [
                'expectedMethodName' => 'patchPets',
                'method' => Method::PATCH,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'PATCH method with parameter' => [
                'expectedMethodName' => 'patchPetsByPetId',
                'method' => Method::PATCH,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'PATCH method with parameter and subresource' => [
                'expectedMethodName' => 'patchPetsByUserIdOrders',
                'method' => Method::PATCH,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'PATCH method with two parameters and subresource' => [
                'expectedMethodName' => 'patchProductsByCategoryIdByProductIdDetails',
                'method' => Method::PATCH,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
            // TRACE
            'TRACE method with operation ID' => [
                'expectedMethodName' => 'receivePets',
                'method' => Method::TRACE,
                'path' => '/pets',
                'operation' => new Operation(operationId: 'receivePets'),
            ],
            'TRACE method with parameter and operation ID' => [
                'expectedMethodName' => 'receivePetsById',
                'method' => Method::TRACE,
                'path' => '/pets/{id}',
                'operation' => new Operation(operationId: 'receivePetsById'),
            ],
            'TRACE method without operation ID' => [
                'expectedMethodName' => 'tracePets',
                'method' => Method::TRACE,
                'path' => '/pets',
                'operation' => new Operation(),
            ],
            'TRACE method with parameter' => [
                'expectedMethodName' => 'tracePetsByPetId',
                'method' => Method::TRACE,
                'path' => '/pets/{petId}',
                'operation' => new Operation(),
            ],
            'TRACE method with parameter and subresource' => [
                'expectedMethodName' => 'tracePetsByUserIdOrders',
                'method' => Method::TRACE,
                'path' => '/pets/{userId}/orders',
                'operation' => new Operation(),
            ],
            'TRACE method with two parameters and subresource' => [
                'expectedMethodName' => 'traceProductsByCategoryIdByProductIdDetails',
                'method' => Method::TRACE,
                'path' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
        ];
    }
}
