<?php

declare(strict_types=1);

namespace Generator\SchemaGenerator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Components;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\MediaTypes;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\Responses;
use Xenos\OpenApi\Model\ResponsesOrReferences;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\Schemas;
use Xenos\OpenApi\Model\SchemasOrReferences;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseFinder;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaFinder;

use function array_keys;
use function array_map;
use function array_values;

class SchemaFinderTest extends TestCase
{
    private SchemaFinder $schemaFinder;

    protected function setUp(): void
    {
        $this->schemaFinder = new SchemaFinder(
            schemaClassNameGenerator: new SchemaClassNameGenerator(),
            responseFinder: new ResponseFinder(
                responseClassNameGenerator: new ResponseClassNameGenerator(
                    config: new Config(namespace: 'PetStore', directory: '/my/dir'),
                    methodNameGenerator: new MethodNameGenerator()
                ),
            )
        );
    }

    /** @param string[] $expectedSchemas */
    #[DataProvider('provideDataForTestFindSchemas')]
    public function testFindSchemas(
        array   $expectedSchemas,
        OpenAPI $openAPI
    ): void {
        $getDescriptions = fn (Schema $schema): ?string => $schema->description;

        self::assertSame(
            expected: $expectedSchemas,
            actual: array_values(array_map(
                $getDescriptions,
                $this->schemaFinder->findAllSchemas($openAPI)->getArrayCopy()
            )),
            message: 'Found schemas are not as expected.'
        );

        self::assertEquals(
            expected: $expectedSchemas,
            actual: array_keys(array_map(
                $getDescriptions,
                $this->schemaFinder->findAllSchemas($openAPI)->getArrayCopy()
            )),
            message: 'List of found schemas should contain schema names as array keys.'
        );
    }

    /** @return array<string, array{expectedSchemas: string[], openAPI: OpenAPI}> */
    public static function provideDataForTestFindSchemas(): array
    {
        return [
            'no schemas' => [
                'expectedSchemas' => [],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
            ],
            'one schema in components' => [
                'expectedSchemas' => ['Pet'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        schemas: new Schemas([
                            'Pet' => new Schema(description: 'Pet')
                        ])
                    )
                ),
            ],
            'two schemas in components' => [
                'expectedSchemas' => ['Pet', 'Shop'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        schemas: new Schemas([
                            'Pet' => new Schema(description: 'Pet'),
                            'Shop' => new Schema(description: 'Shop'),
                        ])
                    )
                ),
            ],
            'schema with sub-schema in components' => [
                'expectedSchemas' => ['Pet', 'PetFood'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        schemas: new Schemas([
                            'Pet' => new Schema(
                                properties: new SchemasOrReferences([
                                    'Food' => new Schema(description: 'PetFood')
                                ]),
                                description: 'Pet'
                            )
                        ])
                    )
                ),
            ],
            'schema with second hierarchy sub-schema in components' => [
                'expectedSchemas' => ['Pet', 'PetFood', 'PetFoodProducer'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        schemas: new Schemas([
                            'Pet' => new Schema(
                                properties: new SchemasOrReferences([
                                    'Food' => new Schema(
                                        properties: new SchemasOrReferences([
                                            'Producer' => new Schema(description: 'PetFoodProducer'),
                                        ]),
                                        description: 'PetFood'
                                    )
                                ]),
                                description: 'Pet'
                            )
                        ])
                    )
                ),
            ],
            'one anonymous schema in responses' => [
                'expectedSchemas' => ['SuccessfulOperationJsonSchema'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'SuccessfulOperation' => new Response(
                                description: 'successful operation',
                                content: new MediaTypes([
                                    'application/json' => new MediaType(
                                        schema: new Schema(description: 'SuccessfulOperationJsonSchema'),
                                    )
                                ])
                            )
                        ])
                    )
                ),
            ],
            'one anonymous schema with numeric key in responses' => [
                'expectedSchemas' => ['Response200JsonSchema'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([ // @phpstan-ignore-line
                            '200' => new Response(
                                description: 'successful operation',
                                content: new MediaTypes([
                                    'application/json' => new MediaType(
                                        schema: new Schema(description: 'Response200JsonSchema'),
                                    )
                                ])
                            )
                        ])
                    )
                ),
            ],
            'two anonymous schemas in responses' => [
                'expectedSchemas' => ['SuccessfulOperationJsonSchema', 'NotFoundJsonSchema'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'SuccessfulOperation' => new Response(
                                description: 'successful operation',
                                content: new MediaTypes([
                                    'application/json' => new MediaType(
                                        schema: new Schema(description: 'SuccessfulOperationJsonSchema'),
                                    )
                                ])
                            ),
                            'NotFound' => new Response(
                                description: 'not found',
                                content: new MediaTypes([
                                    'application/json' => new MediaType(
                                        schema: new Schema(description: 'NotFoundJsonSchema'),
                                    )
                                ])
                            )
                        ])
                    )
                ),
            ],
            'anonymous schema with sub-schema in responses' => [
                'expectedSchemas' => ['SuccessfulOperationJsonSchema', 'SuccessfulOperationJsonSchemaFood'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'SuccessfulOperation' => new Response(
                                description: 'successful operation',
                                content: new MediaTypes([
                                    'application/json' => new MediaType(
                                        schema: new Schema(
                                            properties: new SchemasOrReferences([
                                                'Food' => new Schema(description: 'SuccessfulOperationJsonSchemaFood')
                                            ]),
                                            description: 'SuccessfulOperationJsonSchema'
                                        ),
                                    )
                                ])
                            )
                        ])
                    )
                ),
            ],
            'anonymous schema with second hierarchy sub-schema in responses' => [
                'expectedSchemas' => ['SuccessfulOperationJsonSchema', 'SuccessfulOperationJsonSchemaFood', 'SuccessfulOperationJsonSchemaFoodProducer'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'SuccessfulOperation' => new Response(
                                description: 'successful operation',
                                content: new MediaTypes([
                                    'application/json' => new MediaType(
                                        schema: new Schema(
                                            properties: new SchemasOrReferences([
                                                'Food' => new Schema(
                                                    properties: new SchemasOrReferences([
                                                        'Producer' => new Schema(
                                                            description: 'SuccessfulOperationJsonSchemaFoodProducer'
                                                        ),
                                                    ]),
                                                    description: 'SuccessfulOperationJsonSchemaFood'
                                                )
                                            ]),
                                            description: 'SuccessfulOperationJsonSchema'
                                        ),
                                    )
                                ])
                            )
                        ])
                    )
                ),
            ],
            'one anonymous schema in anonymous response' => [
                'expectedSchemas' => ['GetPet200ResponseJsonSchema'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet'],
                                    responses: new ResponsesOrReferences([
                                        '200' => new Response(
                                            description: 'successful operation',
                                            content: new MediaTypes([
                                                'application/json' => new MediaType(
                                                    schema: new Schema(
                                                        description: 'GetPet200ResponseJsonSchema'
                                                    ),
                                                )
                                            ])
                                        ),
                                    ])
                                ),
                            ),
                        ]
                    ),
                ),
            ],
            'one anonymous schema with sub-schema in anonymous response' => [
                'expectedSchemas' => ['GetPet200ResponseJsonSchema', 'GetPet200ResponseJsonSchemaFood'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet'],
                                    responses: new ResponsesOrReferences([
                                        '200' => new Response(
                                            description: 'successful operation',
                                            content: new MediaTypes([
                                                'application/json' => new MediaType(
                                                    schema: new Schema(
                                                        properties: new SchemasOrReferences([
                                                            'Food' => new Schema(description: 'GetPet200ResponseJsonSchemaFood')
                                                        ]),
                                                        description: 'GetPet200ResponseJsonSchema'
                                                    ),
                                                )
                                            ])
                                        ),
                                    ])
                                ),
                            ),
                        ]
                    ),
                ),
            ],
            'one anonymous schema with second hierarchy sub-schema in anonymous response' => [
                'expectedSchemas' => ['GetPet200ResponseJsonSchema', 'GetPet200ResponseJsonSchemaFood', 'GetPet200ResponseJsonSchemaFoodProducer'],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet'],
                                    responses: new ResponsesOrReferences([
                                        '200' => new Response(
                                            description: 'successful operation',
                                            content: new MediaTypes([
                                                'application/json' => new MediaType(
                                                    schema: new Schema(
                                                        properties: new SchemasOrReferences([
                                                            'Food' => new Schema(
                                                                properties: new SchemasOrReferences([
                                                                    'Producer' => new Schema(description: 'GetPet200ResponseJsonSchemaFoodProducer')
                                                                ]),
                                                                description: 'GetPet200ResponseJsonSchemaFood'
                                                            )
                                                        ]),
                                                        description: 'GetPet200ResponseJsonSchema'
                                                    ),
                                                )
                                            ])
                                        ),
                                    ])
                                ),
                            ),
                        ]
                    ),
                ),
            ],
        ];
    }
}
