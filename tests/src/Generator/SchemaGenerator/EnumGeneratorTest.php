<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

class EnumGeneratorTest extends TestCase
{
    private EnumGenerator $enumClassGenerator;
    private TmpDir $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir('EnumGeneratorTest');
        $config = $this->tmpDir->makeConfig();

        $this->enumClassGenerator = new EnumGenerator(
            $config,
            new Printer(new PsrPrinter())
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
            'enum of strings' => [
                'schemaName' => 'EnumOfStrings',
                'file' => 'Schema/EnumOfStrings.php',
                'schema' => (new Schema(enum: ['available', 'pending', 'sold'])),
            ],
            'enum of integers' => [
                'schemaName' => 'EnumOfIntegers',
                'file' => 'Schema/EnumOfIntegers.php',
                'schema' => (new Schema(enum: [1, 2, 3])),
            ],
        ];
    }
}
