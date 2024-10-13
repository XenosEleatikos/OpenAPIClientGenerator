<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ClientGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

class ClientGeneratorTest extends TestCase
{
    private ClientGenerator $clientGenerator;

    #[DataProvider('provideDataToTestApiFactories')]
    #[DataProvider('provideDataToTestClassComment')]
    public function testGenerate(
        string $namespace,
        OpenAPI $openAPI,
    ): void {
        $tmpDir = new TmpDir($namespace);
        $config = $tmpDir->makeConfig();
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
                methodCommentGenerator: new MethodCommentGenerator()
            )
        );

        $this->clientGenerator->generate($openAPI);

        $file = 'Client.php';

        self::assertFileExists($tmpDir . '/src/' . $file);
        self::assertSame(
            $tmpDir->getFixtureFile($file),
            $tmpDir->getGeneratedFile($file)
        );
    }

    public static function provideDataToTestApiFactories(): array
    {
        return [
            'APIs from declared tags' => [
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client1',
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
                'namespace' => 'ClientGeneratorTest\Client7',
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

    public static function provideDataToTestClassComment(): array
    {
        return [
            'Empty client' => [
                'namespace' => 'ClientGeneratorTest\Client2',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
            ],
            'Client with full information in doc comment' => [
                'namespace' => 'ClientGeneratorTest\Client3',
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
            ],
            'Client with license URL' => [
                'namespace' => 'ClientGeneratorTest\Client4',
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
            ],
            'Client with license identifier' => [
                'namespace' => 'ClientGeneratorTest\Client4',
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
            ],
            'Client with license name only' => [
                'namespace' => 'ClientGeneratorTest\Client5',
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
            ],
        ];
    }
}
