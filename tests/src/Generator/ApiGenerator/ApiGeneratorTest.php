<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ApiGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

class ApiGeneratorTest extends TestCase
{
    #[DataProvider('provideDataForTestGenerateClassComment')]
    public function testGenerateClassComment(string $className, OpenAPI $openAPI, string $tag): void
    {
        $tmpDir = new TmpDir('ApiGeneratorTest\TestGenerateClassComment');
        $apiGenerator = self::getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: $tag);

        self::assertFileExists($tmpDir->getGeneratedFilePath('Api/' . $className . '.php'));

        $reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\\' . $className);
        $reflectionClassFixture = $tmpDir->reflectFixture('Api\\' . $className);

        self::assertIsString(
            actual: $reflectionClassGenerated->getDocComment(),
            message: 'Expected that class ' . $className . ' has a doc comment'
        );

        self::assertSame(
            expected: $reflectionClassFixture->getDocComment(),
            actual: $reflectionClassGenerated->getDocComment(),
            message: 'Doc comment for class ' . $className . ' is not as expected'
        );
    }

    public static function provideDataForTestGenerateClassComment(): array
    {
        return [
            'Undeclared tag' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'tag' => 'Test1',
            ],
            'Declared tag' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(name: 'Test1')
                    ]),
                ),
                'tag' => 'Test1',
            ],
            'Declared tag with description' => [
                'className' => 'Test2Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test2',
                            description: 'Some description'
                        )
                    ]),
                ),
                'tag' => 'Test2',
            ],
            'Declared tag with description and external docs (without description)' => [
                'className' => 'Test3Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test3',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                            )
                        )
                    ]),
                ),
                'tag' => 'Test3',
            ],
            'Declared tag with description and external docs (with empty description)' => [
                'className' => 'Test3Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test3',
                            description: '',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: ''
                            )
                        )
                    ]),
                ),
                'tag' => 'Test3',
            ],
            'Declared tag with description and external docs (with description)' => [
                'className' => 'Test4Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test4',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: 'Find more information here'
                            )
                        )
                    ]),
                ),
                'tag' => 'Test4',
            ],
            'Declared tag with external docs (without description)' => [
                'className' => 'Test5Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test5',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                            )
                        )
                    ]),
                ),
                'tag' => 'Test5',
            ],
            'Declared tag with external docs (with empty description)' => [
                'className' => 'Test5Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test5',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: ''
                            )
                        )
                    ]),
                ),
                'tag' => 'Test5',
            ],
            'Declared tag and external docs (with description)' => [
                'className' => 'Test6Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test6',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: 'Find more information here'
                            )
                        )
                    ]),
                ),
                'tag' => 'Test6',
            ],
        ];
    }

    #[DataProvider('provideDataForTestGenerateApiMethods')]
    public function testGenerateApiMethods(
        string $className,
        OpenAPI $openAPI,
        string $tag
    ): void {
        $tmpDir = new TmpDir('ApiGeneratorTest\TestGenerateApiMethods');
        $apiGenerator = self::getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: $tag);

        //$reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\\' . $className);
        self::assertFileExists($tmpDir->getGeneratedFilePath('Api/' . $className . '.php'));
        self::assertSame(
            $tmpDir->getFixtureFile('Api/' . $className . '.php'),
            $tmpDir->getGeneratedFile('Api/' . $className . '.php')
        );
    }

    public static function provideDataForTestGenerateApiMethods(): array
    {
        return [
            'empty API' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'tag' => 'Test1',
            ],
            'API with GET method' => [
                'className' => 'Test2Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Test2']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test2',
            ],
            'API with PUT method' => [
                'className' => 'Test3Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                put: new Operation(
                                    tags: ['Test3']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test3',
            ],
            'API with POST method' => [
                'className' => 'Test4Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                post: new Operation(
                                    tags: ['Test4']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test4',
            ],
            'API with DELETE method' => [
                'className' => 'Test5Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                delete: new Operation(
                                    tags: ['Test5']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test5',
            ],
            'API with OPTIONS method' => [
                'className' => 'Test6Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                options: new Operation(
                                    tags: ['Test6']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test6',
            ],
            'API with HEAD method' => [
                'className' => 'Test7Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                head: new Operation(
                                    tags: ['Test7']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test7',
            ],
            'API with PATCH method' => [
                'className' => 'Test8Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                patch: new Operation(
                                    tags: ['Test8']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test8',
            ],
            'API with TRACE method' => [
                'className' => 'Test9Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                trace: new Operation(
                                    tags: ['Test9']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test9',
            ],
            'API with all methods on same path' => [
                'className' => 'Test10Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Test10']
                                ),
                                put: new Operation(
                                    tags: ['Test10']
                                ),
                                post: new Operation(
                                    tags: ['Test10']
                                ),
                                delete: new Operation(
                                    tags: ['Test10']
                                ),
                                options: new Operation(
                                    tags: ['Test10']
                                ),
                                head: new Operation(
                                    tags: ['Test10']
                                ),
                                patch: new Operation(
                                    tags: ['Test10']
                                ),
                                trace: new Operation(
                                    tags: ['Test10']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test10',
            ],
        ];
    }

    #[DataProvider('provideDataForTestGenerateReturnValues')]
    public function testGenerateReturnValues(
        string $className,
        OpenAPI $openAPI,
        string $tag
    ): void {
        $tmpDir = new TmpDir('ApiGeneratorTest\TestGenerateReturnValues');
        $apiGenerator = self::getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: $tag);

        //$reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\\' . $className);
        self::assertFileExists($tmpDir->getGeneratedFilePath('Api/' . $className . '.php'));
        self::assertSame(
            $tmpDir->getFixtureFile('Api/' . $className . '.php'),
            $tmpDir->getGeneratedFile('Api/' . $className . '.php')
        );
    }

    public static function provideDataForTestGenerateReturnValues(): array
    {
        return [
            'API with GET method' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Test1'],
                                    operationId: 'getPet',
                                    responses: new ResponsesOrReferences([
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
                'tag' => 'Test1',
            ],
            'API with GET method (without operation ID)' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Test1'],
                                    responses: new ResponsesOrReferences([
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
                'tag' => 'Test1',
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
