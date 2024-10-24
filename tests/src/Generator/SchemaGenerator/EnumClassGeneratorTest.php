<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

class EnumClassGeneratorTest extends TestCase
{
    private EnumClassGenerator $enumClassGenerator;
    private TmpDir $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir('EnumClassGeneratorTest');
        $this->enumClassGenerator = new EnumClassGenerator(
            config: $this->tmpDir->makeConfig(),
            printer: new Printer(new PsrPrinter())
        );
    }

    #[DataProvider('provideDataForGenerateEnumOfStrings')]
    public function testGenerateSchema(
        string $schemaName,
        string $file,
        Schema $schema
    ): void {
        $this->enumClassGenerator->generateSchema($schemaName, $schema, $this->createStub(OpenAPI::class));

        self::assertFileExists($this->tmpDir . '/src/' . $file);
        self::assertSame(
            $this->tmpDir->getFixtureFile($file),
            $this->tmpDir->getGeneratedFile($file)
        );
    }

    public static function provideDataForGenerateEnumOfStrings(): array
    {
        return [
            'enum of integer, float and string' => [
                'schemaName' => 'EnumOfIntegerFloatAndString',
                'file' => 'Schema/EnumOfIntegerFloatAndString.php',
                'schema' => (new Schema(enum: [1, 2.34, 'three'])),
            ],
            'enum of several integers and strings' => [
                'schemaName' => 'EnumOfSeveralIntegersAndStrings',
                'file' => 'Schema/EnumOfSeveralIntegersAndStrings.php',
                'schema' => (new Schema(enum: [1, 2, 'three', 'four'])),
            ],
            'enum of string and true' => [
                'schemaName' => 'EnumOfStringAndTrue',
                'file' => 'Schema/EnumOfStringAndTrue.php',
                'schema' => (new Schema(enum: ['one', true])),
            ],
            'enum of string and false' => [
                'schemaName' => 'EnumOfStringAndFalse',
                'file' => 'Schema/EnumOfStringAndFalse.php',
                'schema' => (new Schema(enum: ['one', false])),
            ],
            'enum of string and true and false' => [
                'schemaName' => 'EnumOfStringAndTrueAndFalse',
                'file' => 'Schema/EnumOfStringAndTrueAndFalse.php',
                'schema' => (new Schema(enum: ['one', true, false])),
            ],
        ];
    }
}
