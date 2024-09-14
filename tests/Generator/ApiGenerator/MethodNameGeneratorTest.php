<?php

declare(strict_types=1);

namespace Generator\ApiGenerator;

use OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use OpenApiClientGenerator\Model\OpenApi\Operation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
        string $method,
        string $path,
        Operation $operation
    ): void {
        self::assertSame(
            $expectedMethodName,
            $this->methodNameGenerator->generateMethodName($method, $path, $operation)
        );
    }

    public static function provideDataForTestGenerateMethodName(): array
    {
        return [
            'with operation ID' => [
                'expectedMethodName' => 'getPetById',
                'method' => 'get',
                'path' => '/pet/{petId}',
                'operation' => new Operation(operationId: 'getPetById'),
            ],
            'with parameter' => [
                'expectedMethodName' => 'getPetByPetId',
                'method' => 'get',
                'path' => '/pet/{petId}',
                'operation' => new Operation(),
            ],
            'with parameter and subresource' => [
                'expectedMethodName' => 'getPetByUserIdOrders',
                'method' => 'get',
                'path' => '/pet/{userId}/orders',
                'operation' => new Operation(),
            ],
            'with two parameters and subresource' => [
                'expectedMethodName' => 'getProductByCategoryIdByProductIdDetails',
                'method' => 'get',
                'path' => '/product/{categoryId}/{productId}/details',
                'operation' => new Operation(),
            ],
        ];
    }
}
