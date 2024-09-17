<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;

use function sys_get_temp_dir;
use function time;

class EnumGeneratorTest extends TestCase
{
    private EnumGenerator $enumGenerator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/openApiClient/' . time();
        $config = new Config(namespace: 'OpenApiClientGeneratorFixture', directory: $this->tmpDir);

        $this->enumGenerator = new EnumGenerator(
            $config,
            new Printer(new PsrPrinter())
        );
    }

    #[DataProvider('provideDataForGenerateEnumOfStrings')]
    public function testGenerateEnumOfStrings(
        string $schemaName,
        string $file,
        Schema $schema
    ): void {
        $this->enumGenerator->generateSchema($schemaName, $schema);

        self::assertFileExists($this->tmpDir . '/src/' . $file);
        self::assertFileEquals(
            __DIR__ . '/../../../fixtures/' . $file,
            $this->tmpDir . '/src/' . $file
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
            'enum of mixed type' => [
                'schemaName' => 'EnumOfIntegers',
                'file' => 'Schema/EnumOfIntegers.php',
                'schema' => (new Schema(enum: [1, 2, 3])),
            ],
        ];
    }
}
