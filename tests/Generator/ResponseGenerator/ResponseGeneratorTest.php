<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ResponseGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use stdClass;
use Xenos\OpenApi\Model\Components;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\MediaTypes;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\Schemas;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApi\Model\SchemaTypes;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\ClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\CollectionGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGeneratorTestHelper\Reflection;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function array_keys;
use function class_exists;
use function str_starts_with;
use function strrpos;
use function substr;

#[RunTestsInSeparateProcesses]
class ResponseGeneratorTest extends TestCase
{
    private TmpDir $tmpDir;
    private ResponseGenerator $responseGenerator;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir();
        $config = $this->tmpDir->makeConfig();
        $printer = new Printer(new PsrPrinter());

        $schemaClassNameGenerator = new SchemaClassNameGenerator();

        $this->responseGenerator = new ResponseGenerator(
            config: $config,
            printer: $printer,
            schemaGeneratorContainer: new SchemaGeneratorContainer(
                config: $config,
                schemaClassNameGenerator: $schemaClassNameGenerator,
                classGenerator: new ClassGenerator(
                    schemaClassNameGenerator: $schemaClassNameGenerator,
                    config: $config,
                    printer: $printer,
                ),
                enumGenerator: new EnumGenerator(
                    config: $config,
                    printer: $printer,
                ),
                enumClassGenerator: new EnumClassGenerator($config, $printer),
                collectionGenerator: new CollectionGenerator($config, $printer),
            )
        );
    }

    /**
     * @param array<string, string|class-string<object>> $expectedParameters
     * @param array<string, string> $expectedFactoryParameters
     * @param array{statusCode: string, data: mixed} $testData
     */
    #[DataProvider('provideDataForTestGenerate')]
    public function testGenerate(
        Response $response,
        OpenAPI $openApi,
        array $expectedParameters,
        mixed $expectedContent,
        array $expectedFactoryParameters,
        array $testData
    ): void {
        $fqcn = $this->tmpDir->getFullyQualifiedClassName('Response\Response');

        $this->responseGenerator->generate(
            responses: [$fqcn => $response],
            openAPI: $openApi
        );

        $filePath = $this->tmpDir->getAbsolutePath('Response' . DIRECTORY_SEPARATOR . 'Response.php');

        self::assertFileExists(
            filename: $filePath,
            message: 'Expected file was not generated.'
        );

        include $filePath;

        self::assertTrue(
            condition: class_exists($fqcn),
            message: 'Expected response class ' . $fqcn . ' was not generated.'
        );

        $reflectionClass = $this->tmpDir->reflect('Response\\Response');

        self::assertTrue(
            condition: $reflectionClass->hasMethod('__construct'),
            message: 'Generated response class does not have a constructor.'
        );

        $reflectionMethod = $reflectionClass->getMethod('__construct');

        self::assertTrue(
            condition: $reflectionMethod->isPublic(),
            message: 'The constructor of the generated response class should be public.'
        );

        $parameters = Reflection::getParameters($reflectionMethod);

        self::assertEqualsCanonicalizing(
            expected: array_keys($expectedParameters),
            actual: array_keys($parameters),
            message: 'The constructor arguments are not as expected.'
        );

        self::assertSame(
            expected: array_keys($expectedParameters),
            actual: array_keys($parameters),
            message: 'The constructor arguments are not sorted as expected.'
        );

        self::assertSame(
            expected: $expectedParameters,
            actual: $parameters,
            message: 'The constructor arguments do not have the expected types.'
        );

        foreach ($reflectionMethod->getParameters() as $parameter) {
            self::assertTrue(
                condition: $parameter->isPromoted(),
                message: 'The constructor parameter ' . $parameter->getName() . ' should be promoted.'
            );
            self::assertTrue(
                condition: $reflectionClass->getProperty($parameter->name)->isPublic(),
                message: 'The promoted constructor parameter ' . $parameter->name . ' should be public.'
            );
        }

        // Test factory
        self::assertTrue(
            condition: $reflectionClass->hasMethod('make'),
            message: 'Generated response class does not have the factory method "make()".'
        );

        $reflectionMethod = $reflectionClass->getMethod('make');
        $parameters = Reflection::getParameters($reflectionMethod);

        self::assertEqualsCanonicalizing(
            expected: array_keys($expectedFactoryParameters),
            actual: array_keys($parameters),
            message: 'The arguments of the factory method "make()" are not as expected.'
        );

        self::assertSame(
            expected: array_keys($expectedFactoryParameters),
            actual: array_keys($parameters),
            message: 'The arguments of the factory method "make()" are not sorted as expected.'
        );

        self::assertSame(
            expected: $expectedFactoryParameters,
            actual: $parameters,
            message: 'The arguments of the factory method "make()" do not have the expected types.'
        );

        $responseClassName = $reflectionClass->name;

        if (isset($expectedParameters['content']) && str_starts_with(haystack: $expectedParameters['content'], needle: 'Xenos')) {
            $className = $this->getClassName($expectedParameters['content']);
            $this->tmpDir->addClass(self::createSchemaClass($className), 'Schema');
            $this->tmpDir->require('Schema\\' . $className);
        }

        $result = $responseClassName::make(...$testData); // @phpstan-ignore-line The response class is generated during the test

        self::assertInstanceOf(
            expected: $responseClassName,
            actual: $result,
            message: 'The factory class should return an instance of the response class itself.'
        );

        if (array_key_exists(key: 'content', array: $expectedParameters)) {
            if (str_starts_with(haystack: $expectedParameters['content'], needle: 'Xenos')) {
                /** @var class-string<object> $expectedContentModel */
                $expectedContentModel = $expectedParameters['content'];
                self::assertInstanceOf(
                    expected: $expectedContentModel,
                    actual: $result->content, // @phpstan-ignore-line Content model is generated during the test
                    message: 'The content of the response was expected to be an instance of ' . $expectedContentModel,
                );
                self::assertSame(
                    expected: $expectedContent,
                    actual: $result->content->data, // @phpstan-ignore-line Content model is generated during the test
                    message: 'The response did not instantiate the content schema with the expected data.'
                );
            } else {
                self::assertSame(
                    expected: $expectedContent,
                    actual: $result->content, // @phpstan-ignore-line Content model is generated during the test
                    message: 'The content of the response is not as expected.'
                );
            }
        }
    }

    private function getClassName(string $fullyQualifiedName): string
    {
        $lastBackslashPosition = strrpos($fullyQualifiedName, '\\');

        return $lastBackslashPosition !== false
            ? substr(string: $fullyQualifiedName, offset: $lastBackslashPosition + 1)
            : $fullyQualifiedName;
    }

    private static function createSchemaClass(string $className): ClassType
    {
        $apiClass = new ClassType($className);
        $apiClass->addMethod('make')
            ->setParameters([(new Parameter('data'))->setType('mixed')])
            ->setStatic()
            ->setBody('return new self($data);');

        $apiClass
            ->addMethod('__construct')
            ->addPromotedParameter('data')
            ->setType('mixed')
            ->setPublic();

        return $apiClass;
    }

    /** @return array<string, array{response: Response, openApi: OpenAPI, expectedParameters: array<string, string|class-string<object>>, expectedFactoryParameters: array<string, string>, testData: array<string, mixed>}> */
    public static function provideDataForTestGenerate(): array
    {
        $stdClass = new stdClass();

        return [
            'Response without content' => [
                'response' => new Response(description: 'successful operation'),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                ],
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                ],
                'expectedContent' => null,
                'testData' => [
                    'statusCode' => '200'
                ],
            ],
            'Response with anonymous object as content' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::OBJECT]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'Xenos\OpenApiClientGeneratorFixture\Schema\ResponseJsonSchema'
                ],
                'expectedContent' => $stdClass,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => stdClass::class,
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => $stdClass,
                ],
            ],
            'Response with anonymous array as content' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::ARRAY]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'Xenos\OpenApiClientGeneratorFixture\Schema\ResponseJsonSchema'
                ],
                'expectedContent' => ['test', 'data'],
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'array',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => ['test', 'data'],
                ],
            ],
            'Response with anonymous number as content (integer given)' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::NUMBER]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'int|float'
                ],
                'expectedContent' => 123,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'int|float',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => 123,
                ],
            ],
            'Response with anonymous number as content (float given)' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::NUMBER]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'int|float'
                ],
                'expectedContent' => 123.45,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'int|float',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => 123.45,
                ],
            ],
            'Response with anonymous integer as content' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::INTEGER]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'int'
                ],
                'expectedContent' => 123,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'int',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => 123,
                ],
            ],
            'Response with anonymous string as content' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::STRING]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'string'
                ],
                'expectedContent' => 'some content',
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'string',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => 'some content',
                ],
            ],
            'Response with anonymous boolean as content (true given)' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::BOOLEAN]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'bool'
                ],
                'expectedContent' => true,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'bool',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => true,
                ],
            ],
            'Response with anonymous boolean as content (false given)' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::BOOLEAN]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'bool'
                ],
                'expectedContent' => false,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'bool',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => false,
                ],
            ],
            'Response with anonymous null as content' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Schema(
                                new SchemaTypes([SchemaType::NULL]),
                            ),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'null'
                ],
                'expectedContent' => null,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'null',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => null,
                ],
            ],
            'Response with reference to object schema' => [
                'response' => new Response(
                    description: 'successful operation',
                    content: new MediaTypes([
                        'application/json' => new MediaType(
                            schema: new Reference('#/components/schemas/Pet'),
                        )
                    ])
                ),
                'openApi' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        schemas: new Schemas([
                            'Pet' => new Schema(
                                type: new SchemaTypes([SchemaType::OBJECT]),
                            )
                        ])
                    ),
                ),
                'expectedParameters' => [
                    'statusCode' => 'string',
                    'content' => 'Xenos\OpenApiClientGeneratorFixture\Schema\Pet',
                ],
                'expectedContent' => $stdClass,
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => stdClass::class,
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => $stdClass,
                ],
            ],
        ];
    }
}
