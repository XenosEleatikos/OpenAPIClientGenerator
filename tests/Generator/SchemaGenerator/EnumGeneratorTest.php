<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use UnitEnum;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function array_keys;
use function array_values;
use function count;

#[RunTestsInSeparateProcesses]
class EnumGeneratorTest extends TestCase
{
    private EnumGenerator $enumClassGenerator;
    private TmpDir $tmpDir;

    /** @param array<string, string> $expectedCases */
    private static function assertEnumCasesAreGeneratedCorrectly(
        ReflectionEnum $reflectionEnum,
        array $expectedCases
    ): void {
        $actualCases = self::getCases($reflectionEnum);

        self::assertCount(
            expectedCount: count($expectedCases),
            haystack: $actualCases,
            message: 'The number of enum cases is not as expected.'
        );
        self::assertEqualsCanonicalizing(
            expected: array_values($expectedCases),
            actual: array_values($actualCases),
            message: 'The enum backing values are not as expected'
        );
        self::assertEqualsCanonicalizing(
            expected: array_keys($expectedCases),
            actual: array_keys($actualCases),
            message: 'The enum cases are not named as expected'
        );
        self::assertEquals(
            expected: $expectedCases,
            actual: $actualCases,
            message: 'There is a mismatch between case names and backing values.'
        );
        self::assertSame(
            expected: $expectedCases,
            actual: $actualCases,
            message: 'The enum cases are not sorted as expected'
        );
    }

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir();
        $this->enumClassGenerator = new EnumGenerator(
            config: $this->tmpDir->makeConfig(),
            printer: new Printer(new PsrPrinter()),
        );
    }

    /** @param array<string, string> $expectedCases */
    #[DataProvider('provideDataForGenerateEnumOfStrings')]
    public function testGenerateSchema(
        Schema $schema,
        string $expectedBackingType,
        array $expectedCases
    ): void {
        $this->enumClassGenerator->generateSchema('SomeEnum', $schema, $this->createStub(OpenAPI::class));

        self::assertFileExists(
            filename: $this->tmpDir->getAbsolutePath('Schema/SomeEnum.php'),
            message: 'The expected file was not created.'
        );

        $reflectionClass = $this->tmpDir->reflect('Schema\SomeEnum');
        self::assertTrue(
            condition: $reflectionClass->isEnum(),
            message: 'Expected that an enum was generated.'
        );
        /** @var class-string<UnitEnum> $fullyQualifiedClassName */
        $fullyQualifiedClassName = $this->tmpDir->getFullyQualifiedClassName('Schema\SomeEnum');
        $reflectionEnum = new ReflectionEnum($fullyQualifiedClassName);
        self::assertFalse(
            condition: $reflectionClass->isAbstract(),
            message: 'The enum should not be abstract.'
        );
        self::assertTrue(
            condition: $reflectionEnum->isBacked(),
            message: 'Expected that the generated enum was backed.'
        );
        self::assertSame(
            expected: $expectedBackingType,
            actual: $reflectionEnum->getBackingType()->getName(),
            message: 'The enums backing type is not as expected',
        );

        self::assertEnumCasesAreGeneratedCorrectly($reflectionEnum, $expectedCases);
    }

    /** @return array<string, int|string> */
    private static function getCases(ReflectionEnum $reflectionEnum): array
    {
        foreach ($reflectionEnum->getCases() as $case) {
            /** @var ReflectionEnumBackedCase $case */
            $cases[$case->name] = $case->getBackingValue();
        }

        return $cases ?? [];
    }

    /** @return array<string, array{schema: Schema, expectedBackingType: string, expectedCases: array<string, int|string>}> */
    public static function provideDataForGenerateEnumOfStrings(): array
    {
        return [
            'enum of strings' => [
                'schema' => (new Schema(enum: ['available', 'pending', 'sold'])),
                'expectedBackingType' => 'string',
                'expectedCases' => [
                    'available' => 'available',
                    'pending' => 'pending',
                    'sold' => 'sold',
                ],
            ],
            'enum of integers' => [
                'schema' => (new Schema(enum: [1, 2, 3])),
                'expectedBackingType' => 'int',
                'expectedCases' => [
                    'CASE_1' => 1,
                    'CASE_2' => 2,
                    'CASE_3' => 3,
                ]
            ],
        ];
    }
}
