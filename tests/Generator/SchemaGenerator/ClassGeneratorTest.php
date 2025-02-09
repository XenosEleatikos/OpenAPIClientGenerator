<?php

declare(strict_types=1);

namespace Generator\SchemaGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\EnumType;
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
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\CollectionGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGeneratorTestHelper\Reflection;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function array_key_exists;

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
        $schemaClassNameGenerator = new SchemaClassNameGenerator();

        $this->classGenerator = new ClassGenerator(
            schemaClassNameGenerator: $schemaClassNameGenerator,
            config: $config,
            printer: new Printer(new PsrPrinter()),
        );

        new SchemaGeneratorContainer(
            config: $config,
            schemaClassNameGenerator: $schemaClassNameGenerator,
            classGenerator: $this->classGenerator,
            enumGenerator: new EnumGenerator(
                config: $config,
                printer: $printer,
            ),
            enumClassGenerator: new EnumClassGenerator($config, $printer),
            collectionGenerator: new CollectionGenerator($config, $printer),
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
     * @param array<string, array{same: mixed, instanceOf: class-string<object>, valueIsSame: mixed, valueEquals: mixed}> $expectedProperties
     * @param array<int, ClassType|EnumType> $requiredSchemas
     */
    #[DataProvider('provideSchemasWithScalarProperties')]
    #[DataProvider('provideSchemasWithArrayProperties')]
    #[DataProvider('provideSchemasWithObjectProperties')]
    #[DataProvider('provideSchemasWithEnumProperties')]
    #[DataProvider('provideSchemasWithAdditionalProperties')]
    public function testGenerateSchema(
        array $expectedParameters,
        array $expectedProperties,
        stdClass $testData,
        Schema $schema,
        array $requiredSchemas = [],
    ): void {
        $openAPI = new OpenAPI(
            openapi: Version::make('3.1.0'),
            info: new Info('Pet Shop API', '1.0.0'),
        );

        foreach ($requiredSchemas as $requiredSchema) {
            $this->tmpDir->addClass(
                classType: $requiredSchema,
                namespace: 'Schema'
            );
        }

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
            if (array_key_exists('same', $expectedValue)) {
                self::assertSame(
                    expected: $expectedValue['same'],
                    actual: $result->$propertyName,
                    message: 'The property "' . $propertyName . '" is not as expected.'
                );
            }
            if (array_key_exists('instanceOf', $expectedValue)) {
                self::assertInstanceOf(
                    expected: $expectedValue['instanceOf'],
                    actual: $result->$propertyName,
                    message: 'The property "' . $propertyName . '" is expected to be instance of "' . $expectedValue['instanceOf'] . '".'
                );
            }
            if (array_key_exists('valueIsSame', $expectedValue)) {
                self::assertSame(
                    expected: $expectedValue['valueIsSame'],
                    actual: $result->$propertyName->value, // @phpstan-ignore-line Dependent schema is generated above
                    message: 'The factory method did not pass the expected values to the entity in property "' . $propertyName . '".'
                );
            }
            if (array_key_exists('valueEquals', $expectedValue)) {
                self::assertEquals(
                    expected: $expectedValue['valueEquals'],
                    actual: $result->$propertyName->value,
                    message: 'The factory method did not pass the expected values to the entity in property "' . $propertyName . '".'
                );
            }
        }
    }

    /** @return array<string, array{expectedParameters: array<string, string>, expectedProperties: array<string, array<int|string, mixed>>, testData: stdClass, schema: Schema}> */
    public static function provideSchemasWithScalarProperties(): array
    {
        return [
            'Object with number property (float given)' => [
                'expectedParameters' => [
                    'someProperty' => 'int|float',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same => 123.45'
                    ]
                ],
                'testData' => (object)['someProperty' => 123.45],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::NUMBER])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
            'Object with number property (integer given)' => [
                'expectedParameters' => [
                    'someProperty' => 'int|float',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same' => 123
                    ]
                ],
                'testData' => (object)['someProperty' => 123],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::NUMBER])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
            'Object with integer property' => [
                'expectedParameters' => [
                    'someProperty' => 'int',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same' => 123
                    ]
                ],
                'testData' => (object)['someProperty' => 123],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::INTEGER])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
            'Object with string property' => [
                'expectedParameters' => [
                    'someProperty' => 'string',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same' => 'test data'
                    ]
                ],
                'testData' => (object)['someProperty' => 'test data'],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::STRING])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
            'Object with boolean property (true given)' => [
                'expectedParameters' => [
                    'someProperty' => 'bool',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same' => true
                    ]
                ],
                'testData' => (object)['someProperty' => true],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::BOOLEAN])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
            'Object with boolean property (false given)' => [
                'expectedParameters' => [
                    'someProperty' => 'bool',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same' => false
                    ]
                ],
                'testData' => (object)['someProperty' => false],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::BOOLEAN])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
            'Object with null property' => [
                'expectedParameters' => [
                    'someProperty' => 'null',
                ],
                'expectedProperties' => [
                    'someProperty' => [
                        'same' => null
                    ]
                ],
                'testData' => (object)['someProperty' => null],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someProperty' => new Schema(
                            type: new SchemaTypes([SchemaType::NULL])
                        ),
                    ]),
                    additionalProperties: false,
                ),
            ],
        ];
    }

    /** @return array<string, array{expectedParameters: array<string, string>, expectedProperties: array<string, array<int|string, mixed>>, testData: stdClass, schema: Schema, requiredSchemas: array<int, ClassType>}> */
    public static function provideSchemasWithArrayProperties(): array
    {
        $arrayObject = new ClassType('SchemaSomeArray');
        $arrayObject->addMethod('make')
            ->setStatic()
            ->addBody('return new self($data);')
            ->addParameter('data');
        $arrayObject->addMethod('__construct')
            ->addPromotedParameter('value');

        return [
            'Object with array property' => [
                'expectedParameters' => [
                    'someArray' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaSomeArray',
                ],
                'expectedProperties' => [
                    'someArray' => [
                        'instanceOf' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaSomeArray',
                        'valueIsSame' => ['value1', 'value2'],
                    ],
                ],
                'testData' => (object)['someArray' => ['value1', 'value2']],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someArray' => new Schema(
                            type: new SchemaTypes([SchemaType::ARRAY])
                        ),
                    ]),
                    additionalProperties: false,
                ),
                'requiredSchemas' => [$arrayObject],
            ],
        ];
    }

    /** @return array<string, array{expectedParameters: array<string, string>, expectedProperties: array<string, array<int|string, mixed>>, testData: stdClass, schema: Schema, requiredSchemas: array<int, ClassType>}> */
    public static function provideSchemasWithObjectProperties(): array
    {
        $objectSchema = new ClassType('SchemaSomeObject');
        $objectSchema->addMethod('make')
            ->setStatic()
            ->addBody('return new self($data);')
            ->addParameter('data');
        $objectSchema->addMethod('__construct')
            ->addPromotedParameter('value');

        return [
            'Object with object property' => [
                'expectedParameters' => [
                    'someObject' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaSomeObject',
                ],
                'expectedProperties' => [
                    'someObject' => [
                        'instanceOf' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaSomeObject',
                        'valueEquals' => (object)['value1', 'value2'],
                    ],
                ],
                'testData' => (object)['someObject' => (object)['value1', 'value2']],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someObject' => new Schema(
                            type: new SchemaTypes([SchemaType::OBJECT])
                        ),
                    ]),
                    additionalProperties: false,
                ),
                'requiredSchemas' => [$objectSchema],
            ],
        ];
    }

    /** @return array<string, array{expectedParameters: array<string, string>, expectedProperties: array<string, array<int|string, mixed>>, testData: stdClass, schema: Schema, requiredSchemas: array<int, EnumType>}> */
    public static function provideSchemasWithEnumProperties(): array
    {
        $enum = new EnumType('SchemaSomeEnum');
        $enumCase = (new EnumCase('VALUE1'))->setValue('value1');
        $enum->setCases([$enumCase]);

        return [
            'Object with enum-of-strings property' => [
                'expectedParameters' => [
                    'someEnum' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaSomeEnum',
                ],
                'expectedProperties' => [
                    'someEnum' => [
                        'instanceOf' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaSomeEnum',
                        'valueIsSame' => 'value1',
                    ],
                ],
                'testData' => (object)['someEnum' => 'value1'],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'someEnum' => new Schema(
                            type: new SchemaTypes([SchemaType::STRING]),
                            enum: ['value1', 'value2'],
                        ),
                    ]),
                    additionalProperties: false,
                ),
                'requiredSchemas' => [$enum],
            ],
        ];
    }

    /** @return array<string, array{expectedParameters: array<string, string>, expectedProperties: array<string, array<int|string, mixed>>, testData: stdClass, schema: Schema}> */
    public static function provideSchemasWithAdditionalProperties(): array
    {
        $arrayObject = new ClassType('SchemaAdditionalProperties');
        $arrayObject->addMethod('make')
            ->setStatic()
            ->addBody('return new self($data);')
            ->addParameter('data');
        $arrayObject->addMethod('__construct')
            ->addPromotedParameter('value');

        return [
            'Object with only additional properties' => [
                'expectedParameters' => [
                    'additionalProperties' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaAdditionalProperties',
                ],
                'expectedProperties' => [
                    'additionalProperties' => [
                        'instanceOf' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaAdditionalProperties',
                        'valueIsSame' => [123.45],
                    ],
                ],
                'testData' => (object)[123.45],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    additionalProperties: true,
                ),
                'requiredSchemas' => [
                    $arrayObject
                ]
            ],
            'Object with string property and additional properties' => [
                'expectedParameters' => [
                    'food' => 'string',
                    'additionalProperties' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaAdditionalProperties',
                ],
                'expectedProperties' => [
                    'food' => [
                        'same' => 'dog food',
                    ],
                    'additionalProperties' => [
                        'instanceOf' => 'Xenos\OpenApiClientGeneratorFixture\Schema\SchemaAdditionalProperties',
                        'valueIsSame' => ['color' => 'brown'],
                    ],
                ],
                'testData' => (object)['food' => 'dog food', 'color' => 'brown'],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT]),
                    properties: new SchemasOrReferences([
                        'food' => new Schema(
                            type: new SchemaTypes([SchemaType::STRING])
                        ),
                    ]),
                    additionalProperties: true,
                ),
                'requiredSchemas' => [
                    $arrayObject
                ]
            ],
        ];
    }
}
