<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ApiGenerator;

use LogicException;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Xenos\OpenApi\Model\ExternalDocumentation;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\MediaTypes;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\ResponsesOrReferences;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApi\Model\Tags;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ApiGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ClassCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGeneratorTestHelper\Reflection;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function count;
use function file_get_contents;
use function trim;

#[RunTestsInSeparateProcesses]
class ApiGeneratorTest extends TestCase
{
    #[DataProvider('provideDataForTestGenerateClassComment')]
    public function testGenerateClassComment(
        OpenAPI $openAPI,
        string $expectedClassComment,
    ): void {
        $expectedClassComment = file_get_contents($expectedClassComment)
            ?: throw new LogicException('Invalid fixture path given');

        $tmpDir = new TmpDir();
        $apiGenerator = self::getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: 'Pet');

        self::assertFileExists($tmpDir->getAbsolutePath('Api/PetApi.php')); // @todo extract in separate test

        $reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\PetApi');

        self::assertIsString(
            actual: $reflectionClassGenerated->getDocComment(),
            message: 'Expected that the generated API class has a doc comment'
        );

        self::assertSame(
            expected: trim($expectedClassComment),
            actual: $reflectionClassGenerated->getDocComment(),
            message: 'Doc comment for class API class is not as expected'
        );
    }

    /** @return array<string, array{openAPI: OpenAPI, expectedClassComment: string}> */
    public static function provideDataForTestGenerateClassComment(): array
    {
        return [
            'Undeclared tag' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagName.txt',
            ],
            'Declared tag' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(name: 'Pet')
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagName.txt',
            ],
            'Declared tag with description' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            description: 'Some description'
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndDescription.txt',
            ],
            'Declared tag with description and external docs (without description)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                            )
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndDescriptionAndLinkWithoutDescription.txt',
            ],
            'Declared tag with description and external docs (with empty description)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: ''
                            )
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndDescriptionAndLinkWithoutDescription.txt',
            ],
            'Declared tag with description and external docs (with description)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: 'Find more information here'
                            )
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndDescriptionAndLinkWithDescription.txt',
            ],
            'Declared tag with external docs (without description)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                            )
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndLinkWithoutDescription.txt',
            ],
            'Declared tag with external docs (with empty description)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: ''
                            )
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndLinkWithoutDescription.txt',
            ],
            'Declared tag and external docs (with description)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Pet',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: 'Find more information here'
                            )
                        )
                    ]),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/tagNameAndLinkWithDescription.txt',
            ],
        ];
    }

    /** @param string[] $expectedMethodNames */
    #[DataProvider('provideDataForTestGenerateApiMethodNames')]
    public function testGenerateApiMethodNames(
        OpenAPI $openAPI,
        array $expectedMethodNames,
    ): void {
        $tmpDir = new TmpDir();
        $apiGenerator = self::getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: 'Pet');

        $reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\PetApi');

        $apiMethodsGenerated = Reflection::getMethodNames($reflectionClassGenerated, ['__construct']);

        self::assertCount(
            expectedCount: count($expectedMethodNames),
            haystack: $apiMethodsGenerated,
            message: 'Number of API methods is not as expected'
        );
        self::assertEqualsCanonicalizing(
            expected: $expectedMethodNames,
            actual: $apiMethodsGenerated,
            message: 'API methods are not named as expected'
        );
        self::assertSame(
            expected: $expectedMethodNames,
            actual: $apiMethodsGenerated,
            message: 'API methods are not sorted as expected'
        );
    }

    /** @return array<string, array{openAPI: OpenAPI, expectedMethodNames: string[]}> */
    public static function provideDataForTestGenerateApiMethodNames(): array
    {
        return [
            'empty API' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedMethodNames' => [],
            ],
            'API with GET method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['getPet'],
            ],
            'API with PUT method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                put: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['putPet'],
            ],
            'API with POST method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                post: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['postPet'],
            ],
            'API with DELETE method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                delete: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['deletePet'],
            ],
            'API with OPTIONS method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                options: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['optionsPet'],
            ],
            'API with HEAD method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                head: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['headPet'],
            ],
            'API with PATCH method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                patch: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['patchPet'],
            ],
            'API with TRACE method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                trace: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['tracePet'],
            ],
            'API with all methods on same path' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet']
                                ),
                                put: new Operation(
                                    tags: ['Pet']
                                ),
                                post: new Operation(
                                    tags: ['Pet']
                                ),
                                delete: new Operation(
                                    tags: ['Pet']
                                ),
                                options: new Operation(
                                    tags: ['Pet']
                                ),
                                head: new Operation(
                                    tags: ['Pet']
                                ),
                                patch: new Operation(
                                    tags: ['Pet']
                                ),
                                trace: new Operation(
                                    tags: ['Pet']
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedMethodNames' => ['getPet', 'putPet', 'postPet', 'deletePet', 'optionsPet', 'headPet', 'patchPet', 'tracePet'],
            ],
        ];
    }

    #[DataProvider('provideDataForTestGenerateReturnValues')]
    public function testGenerateReturnValues(
        OpenAPI $openAPI,
        string $expectedReturnType
    ): void {
        $tmpDir = new TmpDir();
        $apiGenerator = self::getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: 'Pet');

        $reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\PetApi');

        $apiMethodsGenerated = Reflection::getMethodNames($reflectionClassGenerated, ['__construct']);

        if (count($apiMethodsGenerated) !== 1) {
            throw new RuntimeException('Pre-conditions for the test are not fulfilled: we want to test the return types of an API with exactly one API method.');
        }

        $returnTypeGenerated = $reflectionClassGenerated->getMethod($apiMethodsGenerated[0])->getReturnType();

        self::assertEqualsCanonicalizing(
            $expectedReturnType,
            (string)$returnTypeGenerated
        );
    }

    /** @return array<string, array{openAPI: OpenAPI, expectedReturnType: string}> */
    public static function provideDataForTestGenerateReturnValues(): array
    {
        return [
            'API with GET method' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet'],
                                    operationId: 'getPet',
                                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                                        '200' => new Response(
                                            description: 'Successful operation',
                                            content: new MediaTypes([
                                                'application/json' => new MediaType()
                                            ]),
                                        )
                                    ]),
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedReturnType' => 'Xenos\OpenApiClientGeneratorFixture\Response\GetPet200Response',
            ],
            'API with GET method (without operation ID)' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Pet'],
                                    responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                                        '200' => new Response(
                                            description: 'Successful operation',
                                            content: new MediaTypes([
                                                'application/json' => new MediaType()
                                            ]),
                                        )
                                    ]),
                                ),
                            ),
                        ]
                    ),
                ),
                'expectedReturnType' => 'Xenos\OpenApiClientGeneratorFixture\Response\GetPet200Response',
            ],
        ];
    }

    private static function getApiGenerator(TmpDir $tmpDir): ApiGenerator
    {
        $config = $tmpDir->makeConfig();

        return new ApiGenerator(
            config: $config,
            printer: new Printer(new PsrPrinter()),
            methodNameGenerator: new MethodNameGenerator(),
            classCommentGenerator: new ClassCommentGenerator(),
            methodCommentGenerator: new MethodCommentGenerator(),
            classNameGenerator: new ResponseClassNameGenerator($config),
        );
    }
}
