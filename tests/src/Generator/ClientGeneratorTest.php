<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApi\Model\Tags;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function time;
use function sys_get_temp_dir;

class ClientGeneratorTest extends TestCase
{
    private ClientGenerator $clientGenerator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/openApiClient/' . time();
    }

    #[DataProvider('provideDataForTestGenerate')]
    public function testGenerate(
        string $namespace,
        OpenAPI $openAPI,
    ): void {
        $this->clientGenerator = new ClientGenerator(
            config: new Config(namespace: 'Xenos\OpenApiClientGeneratorFixture\\' . $namespace, directory: $this->tmpDir),
            printer: new Printer(new PsrPrinter())
        );

        $this->clientGenerator->generate($openAPI);

        $file = 'Client.php';

        self::assertFileExists($this->tmpDir . '/src/' . $file);
        self::assertFileEquals(
            __DIR__ . '/../../../fixtures/' . $namespace . '/' . $file,
            $this->tmpDir . '/src/' . $file
        );
    }

    public static function provideDataForTestGenerate(): array
    {
        return [
            'Empty client' => [
                'namespace' => 'Client2',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
            ],
            'APIs from declared tags' => [
                'namespace' => 'Client1',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags(
                        [
                            new Tag(name: 'pet'),
                            new Tag(name: 'store'),
                            new Tag(name: 'user'),
                        ]
                    )
                ),
            ],
            'APIs from not declared tags' => [
                'namespace' => 'Client1',
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
            'APIs from not declared tags with double occurrences' => [
                'namespace' => 'Client1',
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
            'APIs from several not declared tags in same path item' => [
                'namespace' => 'Client1',
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
            'APIs from declared tags which are used in operations' => [
                'namespace' => 'Client1',
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
            'Several declared tags which are used in the same operation' => [
                'namespace' => 'Client1',
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
            'Several not declared tags which are used in the same operation' => [
                'namespace' => 'Client1',
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
                'namespace' => 'Client1',
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
            'APIs from declared and not declared tags' => [
                'namespace' => 'Client1',
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
                        ]
                    ),
                ),
            ],
        ];
    }
}
