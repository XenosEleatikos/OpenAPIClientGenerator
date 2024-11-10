<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use LogicException;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ValueError;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGeneratorTestHelper\Reflection;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function array_keys;
use function count;
use function method_exists;
use function property_exists;

#[RunTestsInSeparateProcesses]
class EnumClassGeneratorTest extends TestCase
{
    private EnumClassGenerator $enumClassGenerator;
    private TmpDir $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir();
        $this->enumClassGenerator = new EnumClassGenerator(
            config: $this->tmpDir->makeConfig(),
            printer: new Printer(new PsrPrinter())
        );
    }

    /** @param array<string, mixed> $cases */
    #[DataProvider('provideDataForTestGenerateSchema')]
    public function testGenerateSchema(
        Schema $schema,
        array $cases,
        string $type,
    ): void {
        $this->enumClassGenerator->generateSchema('EnumClass', $schema, $this->createStub(OpenAPI::class));

        self::assertFileExists(
            filename: $this->tmpDir->getAbsolutePath('Schema/EnumClass.php'),
            message: 'The expected file was not created.'
        );

        $reflectionClass = $this->tmpDir->reflect('Schema\EnumClass');
        self::assertFalse(
            condition: $reflectionClass->isAbstract(),
            message: 'The enum class should not be abstract.'
        );
        self::assertConstructorIsGeneratedCorrectly($reflectionClass, $type);
        self::assertValuePropertyIsGeneratedCorrectly($reflectionClass, $type);
        self::assertFactoryMethodsAreGeneratedCorrectly($reflectionClass, $cases);
        self::assertFromMethodIsGeneratedCorrectly($reflectionClass, $type, $cases);
    }

    #[TestWith(['two', 'two is not a valid backing value for enum Xenos\OpenApiClientGeneratorFixture\Schema\EnumClass'])]
    #[TestWith([3, '3 is not a valid backing value for enum Xenos\OpenApiClientGeneratorFixture\Schema\EnumClass'])]
    #[TestWith([4.5, '4.5 is not a valid backing value for enum Xenos\OpenApiClientGeneratorFixture\Schema\EnumClass'])]
    public function testFromThrowsValueError(
        mixed $value,
        string $expectedMessage,
    ): void {
        $schema = (new Schema(enum: ['one', 2, 3.4]));
        $this->enumClassGenerator->generateSchema('EnumClass', $schema, $this->createStub(OpenAPI::class));

        $className = $this->tmpDir->reflect('Schema\EnumClass')->name;

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage($expectedMessage);

        if (!method_exists($className, 'from')) { // Assertion was defined above
            throw new LogicException('The from() method does not exist.');
        }

        $className::from($value);
    }

    /** @return array<string, array{schema: Schema, cases: array<string, mixed>, type: string}> */
    public static function provideDataForTestGenerateSchema(): array
    {
        return [
            'enum of integer, float and string' => [
                'schema' => (new Schema(enum: [1, 2.34, 'three'])),
                'cases' => [
                    'case1' => 1,
                    'case2_34' => 2.34,
                    'caseThree' => 'three',
                ],
                'type' => 'string|int|float',
            ],
            'enum of several integers and strings' => [
                'schema' => (new Schema(enum: [1, 2, 'three', 'four'])),
                'cases' => [
                    'case1' => 1,
                    'case2' => 2,
                    'caseThree' => 'three',
                    'caseFour' => 'four',
                ],
                'type' => 'string|int',
            ],
            'enum of string and true' => [
                'schema' => (new Schema(enum: ['one', true])),
                'cases' => [
                    'caseOne' => 'one',
                    'caseTrue' => true,
                ],
                'type' => 'string|true',
            ],
            'enum of string and false' => [
                'schema' => (new Schema(enum: ['one', false])),
                'cases' => [
                    'caseOne' => 'one',
                    'caseFalse' => false,
                ],
                'type' => 'string|false',
            ],
            'enum of string and true and false' => [
                'schema' => (new Schema(enum: ['one', true, false])),
                'cases' => [
                    'caseOne' => 'one',
                    'caseTrue' => true,
                    'caseFalse' => false,
                ],
                'type' => 'string|bool',
            ],
        ];
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<string, mixed> $cases
     */
    private static function assertFactoryMethodsAreGeneratedCorrectly(ReflectionClass $reflectionClass, array $cases): void
    {
        $factoryMethods = Reflection::getMethodNames($reflectionClass, ['__construct', 'from']);

        self::assertCount(
            expectedCount: count($cases),
            haystack: $factoryMethods,
            message: 'Number of factory methods is not as expected.'
        );
        self::assertEqualsCanonicalizing(
            expected: array_keys($cases),
            actual: $factoryMethods,
            message: 'The generated enum class does not contain the expected factory methods.'
        );
        self::assertSame(
            expected: array_keys($cases),
            actual: $factoryMethods,
            message: 'The factory methods are not sorted as expected.'
        );

        foreach ($cases as $factory => $expectedResult) {
            $reflectionMethod = $reflectionClass->getMethod($factory);

            self::assertTrue(
                condition: $reflectionMethod->isPublic(),
                message: 'Factory methods should be public.'
            );

            self::assertTrue(
                condition: $reflectionMethod->isStatic(),
                message: 'Factory methods should be static.'
            );

            self::assertEmpty(
                actual: $reflectionMethod->getParameters(),
                message: 'Factory methods should not have any parameters.'
            );

            self::assertSame(
                expected: 'self',
                actual: (string)$reflectionMethod->getReturnType(),
                message: 'The return type of factory methods should always be "self".'
            );

            $className = $reflectionClass->name;

            $instance = $className::$factory();

            if (!property_exists($instance, 'value')) { // Assertion was defined above
                throw new LogicException('The value property does not exist.');
            }

            self::assertInstanceOf(
                expected: $className,
                actual: $instance,
                message: 'Factory method should return an instance of the enum class.'
            );
            self::assertSame(
                expected: $instance,
                actual: $className::$factory(),
                message: 'Factory methods should implement the singleton pattern and always return the same instance.'
            );
            self::assertSame(
                expected: $expectedResult,
                actual: $instance->value, // @phpstan-ignore-line
                message: 'The value of the enum instance is not as expected.'
            );
        }
    }

    /** @param ReflectionClass<object> $reflectionClass */
    private static function assertValuePropertyIsGeneratedCorrectly(
        ReflectionClass $reflectionClass,
        string $expectedType
    ): void {
        self::assertCount(
            expectedCount: 1,
            haystack: $reflectionClass->getProperties(),
            message: 'The enum class is expected to have only one property containing its value',
        );
        $reflectionProperty = $reflectionClass->getProperties()[0];
        self::assertSame(
            expected: 'value',
            actual: $reflectionProperty->name,
            message: 'The enum class is expected to have a property named "value".',
        );
        self::assertTrue(
            condition: $reflectionProperty->isPublic(),
            message: 'The value property is expected to be public.'
        );
        self::assertFalse(
            condition: $reflectionProperty->isStatic(),
            message: 'The value property is not expected to be static.'
        );
        self::assertTrue(
            condition: $reflectionProperty->hasType(),
            message: 'The value property is expected to have a type declaration.',
        );
        self::assertSame(
            expected: $expectedType,
            actual: (string)$reflectionProperty->getType(),
            message: 'The type declaration of the value property is not as expected.',
        );
    }

    /** @param ReflectionClass<object> $reflectionClass */
    private static function assertConstructorIsGeneratedCorrectly(
        ReflectionClass $reflectionClass,
        string $expectedType
    ): void {
        self::assertTrue(
            condition: $reflectionClass->hasMethod(name: '__construct'),
            message: 'Enum class should have a constructor.'
        );

        $constructorReflectionMethod = $reflectionClass->getMethod(name: '__construct');

        self::assertTrue(
            condition: $constructorReflectionMethod->isPrivate(),
            message: 'Enum class constructor should be private.'
        );
        self::assertCount(
            expectedCount: 1,
            haystack: $constructorReflectionMethod->getParameters(),
            message: 'Enum class constructor should just have one parameter.'
        );
        self::assertSame(
            expected: 'value',
            actual: $constructorReflectionMethod->getParameters()[0]->name,
            message: 'Enum class constructor is expected to have one parameter named "value".'
        );
        self::assertTrue(
            condition: $constructorReflectionMethod->getParameters()[0]->hasType(),
            message: 'The constructor parameter is expected to have a type declaration.',
        );
        self::assertSame(
            expected: $expectedType,
            actual: (string)$constructorReflectionMethod->getParameters()[0]->getType(),
            message: 'The type hint for the constructor parameter is not as expected.'
        );
    }

    /**
     * @param ReflectionClass<object> $reflectionClass
     * @param array<string, mixed> $cases
     */
    private static function assertFromMethodIsGeneratedCorrectly(
        ReflectionClass $reflectionClass,
        string $expectedType,
        array $cases,
    ): void {
        self::assertTrue(
            condition: $reflectionClass->hasMethod(name: 'from'),
            message: 'Enum class should have a from() method".'
        );

        $fromReflectionMethod = $reflectionClass->getMethod(name: 'from');

        self::assertTrue(
            condition: $fromReflectionMethod->isPublic(),
            message: 'The from() method should be public.'
        );
        self::assertTrue(
            condition: $fromReflectionMethod->isStatic(),
            message: 'The from() method should be static.'
        );
        self::assertCount(
            expectedCount: 1,
            haystack: $fromReflectionMethod->getParameters(),
            message: 'The from() method should just have one parameter.'
        );
        self::assertSame(
            expected: 'value',
            actual: $fromReflectionMethod->getParameters()[0]->name,
            message: 'The from() method is expected to have one parameter named "value".'
        );
        self::assertTrue(
            condition: $fromReflectionMethod->getParameters()[0]->hasType(),
            message: 'The from() method is expected to have a type declaration.',
        );
        self::assertSame(
            expected: $expectedType,
            actual: (string)$fromReflectionMethod->getParameters()[0]->getType(),
            message: 'The type hint for the from() method parameter is not as expected.'
        );

        $className = $reflectionClass->name;

        if (!method_exists($className, 'from')) { // Assertion was defined above
            throw new LogicException('The from() method does not exist.');
        }

        foreach ($cases as $factory => $expectedResult) {
            $instance = $className::from($expectedResult);
            self::assertInstanceOf(
                expected: $className,
                actual: $instance,
                message: 'The from() method should return an instance of the enum class.'
            );
            self::assertSame(
                expected: $instance,
                actual: $className::from($expectedResult),
                message: 'The from() method should implement the singleton pattern and always return the same instance.'
            );
            self::assertSame(
                expected: $instance->$factory(),
                actual: $className::from($expectedResult),
                message: 'The from() method should return the same singleton as the factory method.'
            );
        }
    }
}
