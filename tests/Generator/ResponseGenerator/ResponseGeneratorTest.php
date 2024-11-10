<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ResponseGenerator;

use Nette\PhpGenerator\ClassType;
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
            schemaGeneratorContainer: (new SchemaGeneratorContainer(config: $config, schemaClassNameGenerator: $schemaClassNameGenerator))
                ->add(
                    new EnumGenerator(
                        config: $config,
                        printer: $printer
                    ),
                    new EnumClassGenerator(
                        config: $config,
                        printer: $printer
                    ),
                    new ClassGenerator(
                        config: $config,
                        printer: $printer,
                    ),
                ),
        );
    }

    /**
     * @param array<string, string> $expectedParameters
     * @param array<string, string> $expectedFactoryParameters
     * @param array{statusCode: string, data: mixed} $testData
     */
    #[DataProvider('provideDataForTestGenerateConstructor')]
    public function testGenerateConstructor(
        Response $response,
        OpenAPI $openApi,
        array $expectedParameters,
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
            message: 'Expected files have not been generated.'
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
        $factory = $apiClass->addMethod('make')
            ->setStatic()
            ->setBody('return new self();');

        return $apiClass;
    }

    /** @return array<string, array{response: Response, openApi: OpenAPI, expectedParameters: array<string, string>, expectedFactoryParameters: array<string, string>, testData: array<string, mixed>}> */
    public static function provideDataForTestGenerateConstructor(): array
    {
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
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => stdClass::class,
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => new stdClass(),
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
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'string',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => 'some content',
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
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => 'int',
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => 123,
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
                'expectedFactoryParameters' => [
                    'statusCode' => 'string',
                    'data' => stdClass::class,
                ],
                'testData' => [
                    'statusCode' => '200',
                    'data' => new stdClass(),
                ],
            ],
        ];
    }
}
