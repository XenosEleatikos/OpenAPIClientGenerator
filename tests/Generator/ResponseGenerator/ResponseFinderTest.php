<?php

declare(strict_types=1);

namespace Generator\ResponseGenerator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Components;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\Responses;
use Xenos\OpenApi\Model\ResponsesOrReferences;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseFinder;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function array_values;

class ResponseFinderTest extends TestCase
{
    private ResponseFinder $responseFinder;
    private TmpDir $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir();
        $this->responseFinder = new ResponseFinder(
            responseClassNameGenerator: new ResponseClassNameGenerator(
                config: $this->tmpDir->makeConfig(),
                methodNameGenerator: new MethodNameGenerator(),
            ),
        );
    }

    /** @param string[] $expectedResponses */
    #[DataProvider('provideDataForTestFindResponses')]
    public function testFindResponses(
        OpenAPI $openAPI,
        array $expectedResponses,
    ): void {
        $this->tmpDir = new TmpDir();

        self::assertEqualsCanonicalizing(
            expected: array_values($expectedResponses),
            actual: array_values($this->responseFinder->findResponses($openAPI)),
            message: 'The response finder did not find the expected responses.'
        );

        self::assertEquals(
            expected: $expectedResponses,
            actual: $this->responseFinder->findResponses($openAPI),
            message: 'The response finder did not return the expected class names as array keys.'
        );
    }

    /** @return array<string, array{openAPI: OpenAPI, expectedResponses: array<string, Response>}> */
    public static function provideDataForTestFindResponses(): array
    {
        $notFoundResponse = new Response(
            description: 'Entity not found',
        );
        $generalErrorResponse = new Response(
            description: 'An error occurred',
        );
        $successfulOperationResponse = new Response(description: 'successful operation');

        return [
            'empty API' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedResponses' => [],
            ],
            'one response in components' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'NotFound' => $notFoundResponse,
                        ])
                    ),
                ),
                'expectedResponses' => [
                    'Xenos\OpenApiClientGeneratorFixture\Response\NotFound' => $notFoundResponse,
                ],
            ],
            'two responses in components' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'NotFound' => new Response(
                                description: 'Entity not found',
                            ),
                            'GeneralError' => $generalErrorResponse,
                        ])
                    ),
                ),
                'expectedResponses' => [
                    'Xenos\OpenApiClientGeneratorFixture\Response\NotFound' => $notFoundResponse,
                    'Xenos\OpenApiClientGeneratorFixture\Response\GeneralError' => $generalErrorResponse,
                ],
            ],
            'one anonymous response in GET operation' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths([
                        '/pets' => new PathItem(
                            get: new Operation(
                                responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                                    '200' => $successfulOperationResponse
                                ])
                            )
                        )
                    ]),
                ),
                'expectedResponses' => [
                    'Xenos\OpenApiClientGeneratorFixture\Response\GetPets200Response' => $successfulOperationResponse,
                ],
            ],
            'two anonymous responses in GET operation' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths([
                        '/pets' => new PathItem(
                            get: new Operation(
                                responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                                    '200' => $successfulOperationResponse,
                                    '404' => $notFoundResponse,
                                ])
                            )
                        )
                    ]),
                ),
                'expectedResponses' => [
                    'Xenos\OpenApiClientGeneratorFixture\Response\GetPets200Response' => $successfulOperationResponse,
                    'Xenos\OpenApiClientGeneratorFixture\Response\GetPets404Response' => $notFoundResponse,
                ],
            ],
            'one response in components and one anonymous response in GET operation' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths([
                        '/pets' => new PathItem(
                            get: new Operation(
                                responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                                    '200' => $successfulOperationResponse,
                                ])
                            )
                        )
                    ]),
                    components: new Components(
                        responses: new Responses([
                            'NotFound' => new Response(
                                description: 'Entity not found',
                            )
                        ])
                    ),
                ),
                'expectedResponses' => [
                    'Xenos\OpenApiClientGeneratorFixture\Response\NotFound' => $notFoundResponse,
                    'Xenos\OpenApiClientGeneratorFixture\Response\GetPets200Response' => $successfulOperationResponse,
                ],
            ],
        ];
    }
}
