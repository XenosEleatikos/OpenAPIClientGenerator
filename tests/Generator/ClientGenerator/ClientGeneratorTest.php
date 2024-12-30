<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ClientGenerator;

use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use Xenos\OpenApi\Model\Contact;
use Xenos\OpenApi\Model\ExternalDocumentation;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\License;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApi\Model\Tags;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ApiGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator\ClassCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator\ClientGenerator;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGeneratorFixture\Client;
use Xenos\OpenApiClientGeneratorTestHelper\Reflection;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function array_keys;
use function class_exists;
use function file_get_contents;
use function realpath;

#[RunTestsInSeparateProcesses]
class ClientGeneratorTest extends TestCase
{
    private ClientGenerator $clientGenerator;
    private TmpDir $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir();
        $config = $this->tmpDir->makeConfig();
        $printer = new Printer(new PsrPrinter());

        $this->clientGenerator = new ClientGenerator(
            config: $config,
            printer: $printer,
            classCommentGenerator: new ClassCommentGenerator(),
            apiGenerator: new ApiGenerator(
                config: $config,
                printer: $printer,
                methodNameGenerator: new MethodNameGenerator(),
                classCommentGenerator: new \Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ClassCommentGenerator(),
                methodCommentGenerator: new MethodCommentGenerator(),
                classNameGenerator: new ResponseClassNameGenerator(
                    config: $config,
                    methodNameGenerator: new MethodNameGenerator(),
                ),
            )
        );
    }

    public function testGenerateClass(): void
    {
        $this->clientGenerator->generate(
            new OpenAPI(
                openapi: Version::make('3.1.0'),
                info: new Info('Pet Shop API', '1.0.0'),
            )
        );

        self::assertFileExists($this->tmpDir->getAbsolutePath('Client.php'));

        include realpath($this->tmpDir . '/src/Client.php');

        $expectedClass = $this->tmpDir->getFullyQualifiedClassName('Client');

        self::assertTrue(
            condition: class_exists($expectedClass),
            message: 'Expected class ' . $expectedClass . ' does not exist.'
        );
    }

    #[DataProvider('provideDataToTestClassComment')]
    public function testGenerateClassComment(
        OpenAPI $openAPI,
        string $expectedClassComment,
    ): void {
        $expectedClassComment = file_get_contents($expectedClassComment)
            ?: throw new LogicException('Invalid fixture path given.');

        $this->clientGenerator->generate($openAPI);

        self::assertFileExists($this->tmpDir->getAbsolutePath('Client.php'));

        $reflectionClassGenerated = $this->tmpDir->reflect('Client');

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

    /** @param array<string, class-string> $expectedApiFactories */
    #[DataProvider('provideDataToTestApiFactories')]
    public function testGenerateApiFactories(
        array $expectedApiFactories,
        OpenAPI $openAPI,
    ): void {
        $this->clientGenerator->generate($openAPI);

        $reflectionClass = $this->tmpDir->reflect('Client');
        $generatedMethods = Reflection::getMethodNames($reflectionClass, ['__construct']);

        self::assertFileExists($this->tmpDir->getAbsolutePath('Client.php'));
        self::assertApiFactoriesAreGenerated($expectedApiFactories, $generatedMethods);

        // Generate dependencies
        $this->tmpDir->addClass(new ClassType('Config'), 'Config');

        $this->tmpDir->addClass(self::createApiClass('PetApi'), 'Api');
        $this->tmpDir->addClass(self::createApiClass('StoreApi'), 'Api');
        $this->tmpDir->addClass(self::createApiClass('UserApi'), 'Api');

        $httpClient = $this->createStub(ClientInterface::class);
        $config = new ($this->tmpDir->getFullyQualifiedClassName('Config\Config'))();

        /** @var Client $client */
        $client = new $reflectionClass->name($httpClient, $config); // @phpstan-ignore-line This class is generated temporarily during the test

        foreach ($expectedApiFactories as $expectedApiFactory => $expectedReturnType) {
            self::assertApiFactoryHasCorrectSignature($reflectionClass, $expectedApiFactory, $expectedReturnType);
            self::assertApiFactoryWorks($expectedApiFactory, $client, $expectedReturnType, $httpClient, $config);
        }
    }

    /** @return array<string, array{expectedApiFactories: array<string, string>, openAPI: OpenAPI}> */
    public static function provideDataToTestApiFactories(): array
    {
        return [
            'APIs from declared tags' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(get: new Operation(tags: ['pet'])),
                            '/store' => new PathItem(get: new Operation(tags: ['store'])),
                            '/user' => new PathItem(get: new Operation(tags: ['user'])),
                        ]
                    ),
                    tags: new Tags(
                        [
                            new Tag(name: 'pet'),
                            new Tag(name: 'store'),
                            new Tag(name: 'user'),
                        ]
                    ),
                ),
            ],
            'APIs from undeclared tags' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(get: new Operation(tags: ['pet'])),
                            '/store' => new PathItem(get: new Operation(tags: ['store'])),
                            '/user' => new PathItem(get: new Operation(tags: ['user'])),
                        ]
                    ),
                ),
            ],
            'APIs from undeclared tags with double occurrences' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(tags: ['pet']),
                                put: new Operation(tags: ['pet']),
                            ),
                            '/store' => new PathItem(
                                get: new Operation(tags: ['store']),
                                put: new Operation(tags: ['store']),
                            ),
                            '/user' => new PathItem(
                                get: new Operation(tags: ['user']),
                                put: new Operation(tags: ['user']),
                            ),
                        ]
                    ),
                ),
            ],
            'APIs from several undeclared tags in same path item' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(tags: ['pet']),
                                put: new Operation(tags: ['store']),
                                post: new Operation(tags: ['user']),
                            ),
                        ]
                    ),
                ),
            ],
            'Several declared tags which are used in the same operation' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(get: new Operation(tags: ['pet', 'store', 'user'])),
                        ]
                    ),
                    tags: new Tags(
                        [
                            new Tag(name: 'pet'),
                            new Tag(name: 'store'),
                            new Tag(name: 'user'),
                        ]
                    ),
                ),
            ],
            'Several undeclared tags which are used in the same operation' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(get: new Operation(tags: ['pet', 'store', 'user'])),
                        ]
                    ),
                ),
            ],
            'APIs from declared tags which are used in different order' => [
                'expectedApiFactories' => [
                    'pet' => 'Xenos\OpenApiClientGeneratorFixture\Api\PetApi',
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/store' => new PathItem(get: new Operation(tags: ['store'])),
                            '/pet' => new PathItem(get: new Operation(tags: ['pet'])),
                            '/user' => new PathItem(get: new Operation(tags: ['user'])),
                        ]
                    ),
                    tags: new Tags(
                        [
                            new Tag(name: 'pet'),
                            new Tag(name: 'store'),
                            new Tag(name: 'user'),
                        ]
                    ),
                ),
            ],
            'APIs from declared tags, where not all are used' => [
                'expectedApiFactories' => [
                    'store' => 'Xenos\OpenApiClientGeneratorFixture\Api\StoreApi',
                    'user' => 'Xenos\OpenApiClientGeneratorFixture\Api\UserApi',
                ],
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/store' => new PathItem(get: new Operation(tags: ['store'])),
                            '/user' => new PathItem(get: new Operation(tags: ['user'])),
                        ]
                    ),
                    tags: new Tags(
                        [
                            new Tag(name: 'pet'),
                            new Tag(name: 'store'),
                            new Tag(name: 'user'),
                        ]
                    ),
                ),
            ],
        ];
    }

    /** @return array<string, array{openAPI: OpenAPI, expectedClassComment: string}> */
    public static function provideDataToTestClassComment(): array
    {
        return [
            'Empty client' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/titleAndVersion.txt',
            ],
            'Client with full information in doc comment' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info(
                        title: 'Pet Shop API',
                        version: '1.0.0',
                        summary: 'A sample Pet Store Server based on the OpenAPI 3.1',
                        description: 'This is a sample Pet Store Server based on the OpenAPI 3.1 specification. '
                        . ' You can find out more about Swagger at [https://swagger.io](https://swagger.io). In '
                        . 'the third iteration of the pet store, we\'ve switched to the design first approach!' . PHP_EOL
                        . 'You can now help us improve the API whether it\'s by making changes to the definition '
                        . 'itself or to the code. That way, with time, we can improve the API in general, and expose '
                        . 'some of the new features in OAS3.',
                        termsOfService: 'http://swagger.io/terms/',
                        contact: new Contact(
                            name: 'OpenAPI Specification v3.1.0',
                            url: 'https://spec.openapis.org/oas/latest.html',
                            email: 'apiteam@swagger.io',
                        ),
                        license: new License(
                            name: 'MIT License',
                            url: 'https://opensource.org/licenses/MIT'
                        ),
                    ),
                    externalDocs: new ExternalDocumentation(url: 'https://example.com', description: 'Find more info here')
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/fullInformation.txt',
            ],
            'Client with license URL' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info(
                        title: 'Pet Shop API',
                        version: '1.0.0',
                        license: new License(
                            name: 'MIT License',
                            url: 'https://opensource.org/licenses/MIT'
                        ),
                    ),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/license.txt',
            ],
            'Client with license identifier' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info(
                        title: 'Pet Shop API',
                        version: '1.0.0',
                        license: new License(
                            name: 'MIT License',
                            identifier: 'MIT'
                        ),
                    ),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/license.txt',
            ],
            'Client with license name only' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info(
                        title: 'Pet Shop API',
                        version: '1.0.0',
                        license: new License(
                            name: 'MIT License',
                        ),
                    ),
                ),
                'expectedClassComment' => __DIR__.'/fixtures/docComments/licenseNameOnly.txt',
            ],
        ];
    }

    private static function createApiClass(string $className): ClassType
    {
        $apiClass = new ClassType($className);
        $constructor = $apiClass->addMethod('__construct');
        $constructor->addPromotedParameter('httpClient');
        $constructor->addPromotedParameter('config');

        return $apiClass;
    }

    /**
     * @param array<string, class-string> $expectedApiFactories
     * @param string[] $generatedMethods
     */
    private static function assertApiFactoriesAreGenerated(array $expectedApiFactories, array $generatedMethods): void
    {
        self::assertCount(
            expectedCount: count($expectedApiFactories),
            haystack: $generatedMethods,
            message: 'Number of API factories is not as expected'
        );
        self::assertEqualsCanonicalizing(
            expected: array_keys($expectedApiFactories),
            actual: $generatedMethods,
            message: 'API factories are not named as expected'
        );
        self::assertSame(
            expected: array_keys($expectedApiFactories),
            actual: $generatedMethods,
            message: 'API factories are not sorted as expected'
        );
    }

    /** @param ReflectionClass<object> $reflectionClass */
    private static function assertApiFactoryHasCorrectSignature(
        ReflectionClass $reflectionClass,
        string $expectedApiFactory,
        string $expectedReturnType
    ): void {
        $reflectionMethod = $reflectionClass->getMethod($expectedApiFactory);
        self::assertEmpty(
            actual: $reflectionMethod->getParameters(),
            message: 'API factory method is not expected to have any parameter'
        );
        self::assertTrue(
            condition: $reflectionMethod->hasReturnType(),
            message: 'API factory method is not expected to have any return type.'
        );
        self::assertSame(
            expected: $expectedReturnType,
            actual: (string)$reflectionMethod->getReturnType(),
            message: 'The API factories return type is not as expected.'
        );
    }

    /** @param class-string $expectedReturnType */
    private static function assertApiFactoryWorks(
        string $expectedApiFactory,
        Client $client, // @phpstan-ignore-line This class is generated temporarily during the test
        string $expectedReturnType,
        Stub&ClientInterface $httpClient,
        mixed $config
    ): void {
        $api = $client->$expectedApiFactory();

        self::assertInstanceOf(
            expected: $expectedReturnType,
            actual: $api,
            message: 'The API factory does not return the expected API.'
        );
        self::assertSame(
            expected: $api,
            actual: $client->$expectedApiFactory(),
            message: 'The API factory must implement the singleton pattern and always return the same instance.'
        );

        self::assertNotEmpty(
            actual: $api->httpClient ?? null,
            message: 'There is no HTTP client passed to the API class'
        );
        self::assertSame(
            expected: $httpClient,
            actual: $api->httpClient ?? null,
            message: 'The API factory does not pass the expected HTTP client.'
        );
        self::assertNotEmpty(
            actual: $api->config ?? null,
            message: 'There is no config passed to the API class'
        );
        self::assertSame(
            expected: $config,
            actual: $api->config ?? null,
            message: 'The API factory does not pass the expected config.'
        );
    }
}
