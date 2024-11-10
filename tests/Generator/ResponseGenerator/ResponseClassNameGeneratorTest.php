<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ResponseGenerator;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Method;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\ResponsesOrReferences;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function array_map;
use function var_export;

class ResponseClassNameGeneratorTest extends TestCase
{
    private ResponseClassNameGenerator $responseClassNameGenerator;

    protected function setUp(): void
    {
        $this->responseClassNameGenerator = new ResponseClassNameGenerator(
            config: new Config('PetShop', '/test'),
            methodNameGenerator: new MethodNameGenerator()
        );
    }

    #[DataProvider('provideDataForTestCreateClassNameFromOperation')]
    public function testCreateClassNameFromOperation(
        FullyQualifiedClassName $expectedClassName,
        Method $method,
        string $endpoint,
        Operation $operation,
        string $statusCode
    ): void {
        self::assertEquals(
            expected: $expectedClassName,
            actual: $this->responseClassNameGenerator->fromOperation(
                method: $method,
                endpoint: $endpoint,
                operation: $operation,
                statusCode: $statusCode
            ),
            message: 'Generated class name is not as expected.'
        );
    }

    /** @return array<string, array{expectedClassName: FullyQualifiedClassName, method: Method, endpoint: string, operation: Operation, statusCode: string}> */
    public static function provideDataForTestCreateClassNameFromOperation(): array
    {
        return [
            // GET
            'GET method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::GET,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'GET method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::GET,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'GET method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::GET,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'GET method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\GetPetsByPetId200Response'),
                'method' => Method::GET,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'GET method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\GetPetsByUserIdOrders200Response'),
                'method' => Method::GET,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'GET method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\GetProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::GET,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'GET method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\GetPetsDefaultResponse'),
                'method' => Method::GET,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'GetPets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // PUT
            'PUT method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::PUT,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PUT method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::PUT,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PUT method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::PUT,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'PUT method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PutPetsByPetId200Response'),
                'method' => Method::PUT,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PUT method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PutPetsByUserIdOrders200Response'),
                'method' => Method::PUT,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PUT method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PutProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::PUT,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PUT method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PutPetsDefaultResponse'),
                'method' => Method::PUT,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'PutPets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // POST
            'POST method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::POST,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'POST method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::POST,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'POST method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::POST,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'POST method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PostPetsByPetId200Response'),
                'method' => Method::POST,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'POST method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PostPetsByUserIdOrders200Response'),
                'method' => Method::POST,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'POST method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PostProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::POST,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'POST method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PostPetsDefaultResponse'),
                'method' => Method::POST,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'PostPets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // DELETE
            'DELETE method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::DELETE,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'DELETE method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::DELETE,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'DELETE method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::DELETE,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'DELETE method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\DeletePetsByPetId200Response'),
                'method' => Method::DELETE,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'DELETE method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\DeletePetsByUserIdOrders200Response'),
                'method' => Method::DELETE,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'DELETE method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\DeleteProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::DELETE,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'DELETE method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\DeletePetsDefaultResponse'),
                'method' => Method::DELETE,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'DeletePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // OPTIONS
            'OPTIONS method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::OPTIONS,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'OPTIONS method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::OPTIONS,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'OPTIONS method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::OPTIONS,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'OPTIONS method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\OptionsPetsByPetId200Response'),
                'method' => Method::OPTIONS,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'OPTIONS method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\OptionsPetsByUserIdOrders200Response'),
                'method' => Method::OPTIONS,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'OPTIONS method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\OptionsProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::OPTIONS,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'OPTIONS method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\OptionsPetsDefaultResponse'),
                'method' => Method::OPTIONS,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'OptionsPets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // HEAD
            'HEAD method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::HEAD,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'HEAD method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::HEAD,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'HEAD method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::HEAD,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'HEAD method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\HeadPetsByPetId200Response'),
                'method' => Method::HEAD,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'HEAD method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\HeadPetsByUserIdOrders200Response'),
                'method' => Method::HEAD,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'HEAD method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\HeadProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::HEAD,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'HEAD method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\HeadPetsDefaultResponse'),
                'method' => Method::HEAD,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'HeadPets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // PATCH
            'PATCH method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::PATCH,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PATCH method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::PATCH,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PATCH method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::PATCH,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'PATCH method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PatchPetsByPetId200Response'),
                'method' => Method::PATCH,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PATCH method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PatchPetsByUserIdOrders200Response'),
                'method' => Method::PATCH,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PATCH method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PatchProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::PATCH,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'PATCH method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\PatchPetsDefaultResponse'),
                'method' => Method::PATCH,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'PatchPets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            // TRACE
            'TRACE method with operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePets200Response'),
                'method' => Method::TRACE,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'TRACE method with parameter and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsById200Response'),
                'method' => Method::TRACE,
                'endpoint' => '/pets/{id}',
                'operation' => new Operation(
                    operationId: 'ReceivePetsById',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'TRACE method with default response and operation ID' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\ReceivePetsDefaultResponse'),
                'method' => Method::TRACE,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'ReceivePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
            'TRACE method with parameter' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\TracePetsByPetId200Response'),
                'method' => Method::TRACE,
                'endpoint' => '/pets/{petId}',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'TRACE method with parameter and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\TracePetsByUserIdOrders200Response'),
                'method' => Method::TRACE,
                'endpoint' => '/pets/{userId}/orders',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'TRACE method with two parameters and subresource' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\TraceProductsByCategoryIdByProductIdDetails200Response'),
                'method' => Method::TRACE,
                'endpoint' => '/products/{categoryId}/{productId}/details',
                'operation' => new Operation(
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => '200'
            ],
            'TRACE method with operation ID and default response' => [
                'expectedClassName' => new FullyQualifiedClassName('PetShop\Response\TracePetsDefaultResponse'),
                'method' => Method::TRACE,
                'endpoint' => '/pets',
                'operation' => new Operation(
                    operationId: 'TracePets',
                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                        '200' => new Response(description: 'successful operation')
                    ])
                ),
                'statusCode' => 'default'
            ],
        ];
    }

    #[DataProvider('provideComponentNamesAndClassNames')]
    public function testCreateResponseClassNameFromComponentsKey(
        string $componentsKey,
        string $expectedClassName,
    ): void {
        self::assertSame(
            $expectedClassName,
            (string)$this->responseClassNameGenerator->fromComponentsKey($componentsKey)
        );
        self::assertIsValidClassName($expectedClassName);
    }

    #[DataProvider('provideReferencePathsAndClassNames')]
    public function testCreateResponseClassNameFromReferencePath(
        string $referencePath,
        string $expectedClassName,
    ): void {
        self::assertSame(
            $expectedClassName,
            (string)$this->responseClassNameGenerator->fromReferencePath($referencePath)
        );
        self::assertIsValidClassName($expectedClassName);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function provideReferencePathsAndClassNames(): array
    {
        return array_map(
            callback: fn (array $componentNameAndClassName): array => ['components/responses/' . $componentNameAndClassName[0], $componentNameAndClassName[1]],
            array: self::provideComponentNamesAndClassNames(),
        );
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function provideComponentNamesAndClassNames(): array
    {
        return [
            'one word class name upper case' => ['Pet', 'PetShop\Response\Pet'],
            'one word class name lower case' => ['pet', 'PetShop\Response\Pet'],
            'one word class name lower case with number in the end' => ['pet123', 'PetShop\Response\Pet123'],
            'lower dot case' => ['pet.shop', 'PetShop\Response\PetShop'],
            'lower kebap case' => ['class-name', 'PetShop\Response\ClassName'],
            'lower snake case' => ['test_class', 'PetShop\Response\TestClass'],
            'mixed case' => ['my-dog.pet_shop', 'PetShop\Response\MyDogPetShop'],
            'starting with dot' => ['.pet', 'PetShop\Response\Pet'],
            'starting with underscore' => ['_pet', 'PetShop\Response\Pet'],
            'starting with dash' => ['-pet', 'PetShop\Response\Pet']
        ];
    }

    #[DataProvider('provideInvalidComponentKeys')]
    public function testCreateResponseClassNameFromComponentsKeyThrowsInvalidArgumentException(
        string $componentsKey,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component key must be a string matching the regular expression "/^[a-zA-Z0-9._-]+$/", ' . var_export($componentsKey, true) . ' given.');

        $this->responseClassNameGenerator->fromComponentsKey($componentsKey);
    }

    /** @return array<int, array<int, string>>*/
    public static function provideInvalidComponentKeys(): array
    {
        return [
            ['two words'],
            ['@'],
            ['#'],
            ['%'],
            [PHP_EOL],
            ['ä'],
            ['ö'],
            ['ü'],
            ['ß'],
        ];
    }

    private static function assertIsValidClassName(string $expectedClassName): void
    {
        self::assertMatchesRegularExpression(
            pattern: '/^[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*(\\\\[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*)*$/',
            string: $expectedClassName,
            message: ResponseClassNameGenerator::class . ' generated an invalid class name',
        );
    }
}
