<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use ArrayObject;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
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

use function json_decode;

#[RunTestsInSeparateProcesses]
class CollectionGeneratorTest extends TestCase
{
    private TmpDir $tmpDir;
    private CollectionGenerator $collectionGenerator;

    protected function setUp(): void
    {
        $printer = new Printer(new PsrPrinter());
        $this->tmpDir = new TmpDir();
        $config = $this->tmpDir->makeConfig();
        $schemaClassNameGenerator = new SchemaClassNameGenerator();

        $classGenerator = new ClassGenerator(
            schemaClassNameGenerator: $schemaClassNameGenerator,
            config: $config,
            printer: new Printer(new PsrPrinter()),
        );

        $this->collectionGenerator = new CollectionGenerator(
            config: $config,
            printer: new Printer(new PsrPrinter()),
        );

        new SchemaGeneratorContainer(
            config: $config,
            schemaClassNameGenerator: $schemaClassNameGenerator,
            classGenerator: $classGenerator,
            enumGenerator: new EnumGenerator(
                config: $config,
                printer: $printer,
            ),
            enumClassGenerator: new EnumClassGenerator($config, $printer),
            collectionGenerator: $this->collectionGenerator,
        );
    }

    #[DataProvider('provideDataForTestIsResponsible')]
    public function testIsResponsible(bool $expectedResult, Schema $schema): void
    {
        self::assertSame(
            expected: $expectedResult,
            actual: $this->collectionGenerator->isResponsible($schema),
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
                'expectedResult' => false,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::OBJECT,
                    ]),
                ),
            ],
            'array schema' => [
                'expectedResult' => true,
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
            'mixed schema without array' => [
                'expectedResult' => false,
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
            'mixed schema with array' => [
                'expectedResult' => true,
                'schema' => new Schema(
                    type: new SchemaTypes([
                        SchemaType::OBJECT,
                        SchemaType::NUMBER,
                        SchemaType::INTEGER,
                        SchemaType::STRING,
                        SchemaType::BOOLEAN,
                        SchemaType::NULL,
                        SchemaType::ARRAY,
                    ]),
                ),
            ],
        ];
    }

    /**
     * @param array<string, array{same: mixed, instanceOf: class-string<object>, valueIsSame: mixed, valueEquals: mixed}> $expectedItems
     * @param array<int, mixed> $testData
     * @param array<int, ClassType|EnumType> $requiredSchemas
     */
    #[DataProvider('provideSchemasWithScalarProperties')]
    public function testGenerateSchema(
        array  $expectedItems,
        array  $testData,
        Schema $schema,
        array  $requiredSchemas = [],
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

        $fqcn = $this->tmpDir->getFullyQualifiedClassName('Schema\Collection');

        $this->collectionGenerator->generateSchema(
            name: 'Collection',
            schema: $schema,
            openAPI: $openAPI
        );

        $filePath = $this->tmpDir->getAbsolutePath('Schema' . DIRECTORY_SEPARATOR . 'Collection.php');

        self::assertFileExists(
            filename: $filePath,
            message: 'Expected file was not generated.'
        );

        include $filePath;

        self::assertTrue(
            condition: class_exists($fqcn),
            message: 'Expected schema class ' . $fqcn . ' was not generated.'
        );

        $reflectionClass = $this->tmpDir->reflect('Schema\\Collection');

        self::assertTrue(
            condition: $reflectionClass->getParentClass() !== false && $reflectionClass->getParentClass()->getName() === 'ArrayObject',
            message: 'Expected generated class to extend \ArrayObject.'
        );
        self::assertTrue(
            condition: $reflectionClass->hasMethod('__construct'),
            message: 'Generated array object class must have a constructor.'
        );

        $reflectionMethod = $reflectionClass->getMethod('__construct');

        self::assertTrue(
            condition: $reflectionMethod->isPublic(),
            message: 'The constructor of the generated response class should be public.'
        );

        $parameters = Reflection::getParameters($reflectionMethod);

        $expectedParameters = [
            'array' => 'object|array',
            'flags' => 'int',
            'iteratorClass' => 'string',
        ];

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
            self::assertFalse(
                condition: $parameter->isPromoted(),
                message: 'The constructor parameter ' . $parameter->getName() . ' should not be promoted.'
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
            expected: ['data' => 'array'],
            actual: $parameters,
            message: 'The factory method "make()" is expected to have just one data argument of type array.'
        );

        $collectionClassName = $reflectionClass->name;

        /** @var ArrayObject $result */
        $result = $collectionClassName::make($testData); // @phpstan-ignore-line The schema class is generated during the test

        self::assertInstanceOf(
            expected: $collectionClassName,
            actual: $result,
            message: 'The factory class should return an instance of the Schema class itself.'
        );

        foreach ($expectedItems as $key => $expectedItem) {
            foreach ($expectedItem as $assertion => $expectedValue) {
                self::assertArrayHasKey(
                    key: $key,
                    array: $result,
                    message: 'The array does not contain the expected key ' . $key . '.'
                );
                switch ($assertion) {
                    case 'same':
                        self::assertSame(
                            expected: $expectedValue,
                            actual: $result[$key],
                            message: 'The array key ' . $key . ' is not as expected.'
                        );
                        break;
                    case 'instanceOf':
                        self::assertInstanceOf(
                            expected: $expectedValue,
                            actual: $result[$key],
                            message: 'The array key ' . $key . ' is expected to be instance of "' . $expectedValue . '".'
                        );
                        break;
                    case 'valueIsSame':
                        self::assertSame(
                            expected: $expectedValue,
                            actual: $result[$key]->value, // @phpstan-ignore-line Dependent schema is generated above
                            message: 'The factory method did not pass the expected values to the entity in key ' . $key . '.'
                        );
                        break;
                    case 'valueEquals':
                        self::assertIsObject($result[$key]);
                        self::assertEquals(
                            expected: $expectedValue,
                            actual: $result[$key]->value, // @phpstan-ignore-line Dependent schema is generated above
                            message: 'The factory method did not pass the expected values to the entity in key ' . $key . '.'
                        );
                }
            }
        }
    }

    /** @return array<string, array{expectedItems: array<int, array<string, mixed>>, testData: array<int, mixed>, schema: Schema}> */
    public static function provideSchemasWithScalarProperties(): array
    {
        $collectionItems = new ClassType('CollectionItems');
        $collectionItems->addMethod('make')
            ->setStatic()
            ->addBody('return new self($data);')
            ->addParameter('data');
        $collectionItems->addMethod('__construct')
            ->addPromotedParameter('value');

        return [
            'Array of numbers (float given)' => [
                'expectedItems' => [
                    0 => [
                        'same' => 123.45,
                    ],
                ],
                'testData' => [123.45],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::NUMBER])
                    ),
                ),
            ],
            'Array of numbers (integer given)' => [
                'expectedItems' => [
                    0 => [
                        'same' => 123,
                    ],
                ],
                'testData' => [123],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::NUMBER])
                    ),
                ),
            ],
            'Array of strings (one string given)' => [
                'expectedItems' => [
                    0 => [
                        'same' => 'dog',
                    ],
                ],
                'testData' => ['dog'],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::STRING])
                    ),
                ),
            ],
            'Array of booleans (true given)' => [
                'expectedItems' => [
                    0 => [
                        'same' => true,
                    ],
                ],
                'testData' => [true],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::BOOLEAN])
                    ),
                ),
            ],
            'Array of booleans (false given)' => [
                'expectedItems' => [
                    0 => [
                        'same' => false,
                    ],
                ],
                'testData' => [false],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::BOOLEAN])
                    ),
                ),
            ],
            'Array of nulls (one null given)' => [
                'expectedItems' => [
                    0 => [
                        'same' => null,
                    ],
                ],
                'testData' => [null],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::NULL])
                    ),
                ),
            ],
            'Array of objects' => [
                'expectedItems' => [
                    0 => [
                        'valueIsEqual' => json_decode('{"food": "dog food"}'),
                    ],
                ],
                'testData' => [json_decode('{"food": "dog food"}')],
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY]),
                    items: new Schema(
                        type: new SchemaTypes([SchemaType::OBJECT]),
                        properties: new SchemasOrReferences([
                            'food' => new Schema(type: new SchemaTypes([SchemaType::STRING]))
                        ])
                    )
                ),
                'requiredSchemas' => [$collectionItems]
            ],
        ];
    }
}
