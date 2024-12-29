<?php

declare(strict_types=1);

namespace Generator\SchemaGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use stdClass;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemasOrReferences;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApi\Model\SchemaTypes;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\ClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGeneratorTestHelper\Reflection;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

#[RunTestsInSeparateProcesses]
class ClassGeneratorTest extends TestCase
{
    private TmpDir $tmpDir;
    private ClassGenerator $classGenerator;

    protected function setUp(): void
    {
        $printer = new Printer(new PsrPrinter());
        $this->tmpDir = new TmpDir();
        $config = $this->tmpDir->makeConfig();
        $this->classGenerator = new ClassGenerator(
            config: $config,
            printer: new Printer(new PsrPrinter()),
        );

        new SchemaGeneratorContainer(
            config: $config,
            schemaClassNameGenerator: new SchemaClassNameGenerator(),
            classGenerator: $this->classGenerator,
            enumGenerator: new EnumGenerator(
                config: $config,
                printer: $printer,
            ),
            enumClassGenerator: new EnumClassGenerator($config, $printer),
        );
    }

    #[DataProvider('provideDataForTestIsResponsible')]
    public function testIsResponsible(bool $expectedResult, Schema $schema): void
    {
        self::assertSame(
            expected: $expectedResult,
            actual: $this->classGenerator->isResponsible($schema),
            message: 'Responsibility was not determined correctly.'
        );
    }

    /** @return array<string, array{expectedResult: mixed, schema: Schema}> */
    public static function provideDataForTestIsResponsible(): array
    {
        return [
            'empty schema' => [
                'expectedResult' => false,
                'schema' => new Schema(),
            ],
            'empty schema (with empty types object)' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes(),
                ),
            ],
            'object schema' => [
                'expectedResult' => true,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::OBJECT,
                    ]),
                ),
            ],
            'array schema' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::ARRAY,
                    ]),
                ),
            ],
            'number schema' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::NUMBER,
                    ]),
                ),
            ],
            'integer schema' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::INTEGER,
                    ]),
                ),
            ],
            'string schema' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::STRING,
                    ]),
                ),
            ],
            'boolean schema' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::BOOLEAN,
                    ]),
                ),
            ],
            'null schema' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::NULL,
                    ]),
                ),
            ],
            'mixed schema without object' => [
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::ARRAY,
                        SchemaType::NUMBER,
                        SchemaType::INTEGER,
                        SchemaType::STRING,
                        SchemaType::BOOLEAN,
                        SchemaType::NULL,
                    ]),
                ),
            ],
            'mixed schema with object' => [
                'expectedResult' => true,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::OBJECT,
                        SchemaType::NUMBER,
                        SchemaType::INTEGER,
                        SchemaType::STRING,
                        SchemaType::BOOLEAN,
                        SchemaType::NULL,
                    ]),
                ),
            ],
        ];
    }

    /**
     * @param array<string, string> $expectedParameters
     * @param array<string, mixed> $expectedProperties
     */
    #[DataProvider('provideDataForTestGenerateFile')]
    public function testGenerateFile(
        array $expectedParameters,
        array $expectedProperties,
        stdClass $testData,
        Schema $schema,
        OpenAPI $openAPI,
    ): void {
        $fqcn = $this->tmpDir->getFullyQualifiedClassName('Schema\Schema');

        $this->classGenerator->generateSchema(
            name: 'Schema',
            schema: $schema,
            openAPI: $openAPI
        );

        $filePath = $this->tmpDir->getAbsolutePath('Schema' . DIRECTORY_SEPARATOR . 'Schema.php');

        self::assertFileExists(
            filename: $filePath,
            message: 'Expected file was not generated.'
        );

        include $filePath;

        self::assertTrue(
            condition: class_exists($fqcn),
            message: 'Expected schema class ' . $fqcn . ' was not generated.'
        );

        $reflectionClass = $this->tmpDir->reflect('Schema\\Schema');

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

        self::assertSame(
            expected: ['data' => stdClass::class],
            actual: $parameters,
            message: 'The factory method "make()" is expected to have just one data argument of type \stdClass.'
        );

        $schemaClassName = $reflectionClass->name;

        $result = $schemaClassName::make($testData); // @phpstan-ignore-line The schema class is generated during the test

        self::assertInstanceOf(
            expected: $schemaClassName,
            actual: $result,
            message: 'The factory class should return an instance of the Schema class itself.'
        );

        foreach ($expectedProperties as $propertyName => $expectedValue) {
            self::assertSame(
                expected: $expectedValue,
                actual: $result->$propertyName,
                message: 'The property "' . $propertyName . '" is not as expected.'
            );
        }
    }

    /** @return array<string, array{expectedParameters: array<string, string>, expectedProperties: array<string, mixed>, testData: stdClass, schema: Schema, openAPI: OpenAPI}> */
    public static function provideDataForTestGenerateFile(): array
    {
        $openApi = new OpenAPI(
            openapi: Version::make('3.1.0'),
            info: new Info('Pet Shop API', '1.0.0'),
        );

        return [
            'Object with array property' => [
                'expectedParameters' => [
                    'someProperty' => 'array',
                ],
                'expectedProperties' => [
                    'someProperty' => ['test' => 'data']
                ],
                'testData' => (object)['someProperty' => ['test' => 'data']],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::ARRAY])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with number property (float given)' => [
                'expectedParameters' => [
                    'someProperty' => 'int|float',
                ],
                'expectedProperties' => [
                    'someProperty' => 123.45
                ],
                'testData' => (object)['someProperty' => 123.45],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::NUMBER])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with number property (integer given)' => [
                'expectedParameters' => [
                    'someProperty' => 'int|float',
                ],
                'expectedProperties' => [
                    'someProperty' => 123
                ],
                'testData' => (object)['someProperty' => 123],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::NUMBER])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with integer property' => [
                'expectedParameters' => [
                    'someProperty' => 'int',
                ],
                'expectedProperties' => [
                    'someProperty' => 123
                ],
                'testData' => (object)['someProperty' => 123],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::INTEGER])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with string property' => [
                'expectedParameters' => [
                    'someProperty' => 'string',
                ],
                'expectedProperties' => [
                    'someProperty' => 'test data'
                ],
                'testData' => (object)['someProperty' => 'test data'],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::STRING])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with boolean property (true given)' => [
                'expectedParameters' => [
                    'someProperty' => 'bool',
                ],
                'expectedProperties' => [
                    'someProperty' => true
                ],
                'testData' => (object)['someProperty' => true],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::BOOLEAN])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with boolean property (false given)' => [
                'expectedParameters' => [
                    'someProperty' => 'bool',
                ],
                'expectedProperties' => [
                    'someProperty' => false
                ],
                'testData' => (object)['someProperty' => false],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::BOOLEAN])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
            'Object with null property' => [
                'expectedParameters' => [
                    'someProperty' => 'null',
                ],
                'expectedProperties' => [
                    'someProperty' => null
                ],
                'testData' => (object)['someProperty' => null],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::NULL])
                        ),
                    ]),
                ),
                'openAPI' => $openApi,
            ],
        ];
    }
}
