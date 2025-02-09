<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApi\Model\SchemaTypes;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\ClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\CollectionGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorInterface;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

use function get_class;

class SchemaGeneratorContainerTest extends TestCase
{
    private static ClassGenerator $classGenerator;
    private static EnumGenerator $enumGenerator;
    private static EnumClassGenerator $enumClassGenerator;
    private static CollectionGenerator $collectionGenerator;
    private static SchemaGeneratorContainer $schemaGeneratorContainer;

    public static function setUpBeforeClass(): void
    {
        $printer = new Printer(new PsrPrinter());
        $config = (new TmpDir())->makeConfig();

        $schemaClassNameGenerator = new SchemaClassNameGenerator();

        self::$classGenerator = new ClassGenerator(
            schemaClassNameGenerator: $schemaClassNameGenerator,
            config: $config,
            printer: $printer,
        );
        self::$enumGenerator = new EnumGenerator(
            config: $config,
            printer: $printer,
        );
        self::$enumClassGenerator = new EnumClassGenerator($config, $printer);
        self::$collectionGenerator = new CollectionGenerator($config, $printer);

        self::$schemaGeneratorContainer = new SchemaGeneratorContainer(
            config: $config,
            schemaClassNameGenerator: $schemaClassNameGenerator,
            classGenerator: self::$classGenerator,
            enumGenerator: self::$enumGenerator,
            enumClassGenerator: self::$enumClassGenerator,
            collectionGenerator: self::$collectionGenerator,
        );
    }

    #[DataProvider('provideDataForTestGetSchemaGenerator')]
    public function testGetSchemaGenerator(
        Schema $schema,
        ?string $expectedSchemaGenerator,
    ): void {
        $result = self::$schemaGeneratorContainer->getSchemaGenerator($schema);

        /** @var ?SchemaGeneratorInterface $expectedSchemaGenerator */
        $expectedSchemaGenerator = isset($expectedSchemaGenerator)
            ? self::${$expectedSchemaGenerator}
            : null;
        if (isset($expectedSchemaGenerator)) {
            self::assertInstanceOf(
                expected: get_class($expectedSchemaGenerator),
                actual: $result,
                message: 'The wrong responsibility was determined.'
            );
        }

        self::assertSame(
            expected: isset($expectedSchemaGenerator) ? $expectedSchemaGenerator : null,
            actual: $result,
            message: 'The returned instance of the schema generator was not the instance injected into the constructor.'
        );
    }

    /** @return array<string, array{schema: Schema, expectedSchemaGenerator: ?string}> */
    public static function provideDataForTestGetSchemaGenerator(): array
    {
        return [
            'Object schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::OBJECT])
                ),
                'expectedSchemaGenerator' => 'classGenerator',
            ],
            'Array schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::ARRAY])
                ),
                'expectedSchemaGenerator' => 'collectionGenerator',
            ],
            'Number schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::NUMBER])
                ),
                'expectedSchemaGenerator' => null,
            ],
            'Integer schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::INTEGER])
                ),
                'expectedSchemaGenerator' => null,
            ],
            'String schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::STRING])
                ),
                'expectedSchemaGenerator' => null,
            ],
            'Boolean schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::BOOLEAN])
                ),
                'expectedSchemaGenerator' => null,
            ],
            'Null schema given' => [
                'schema' => new Schema(
                    type: new SchemaTypes([SchemaType::NULL])
                ),
                'expectedSchemaGenerator' => null,
            ],
            'Enum of strings given' => [
                'schema' => new Schema(
                    enum: ['value1', 'value2'],
                ),
                'expectedSchemaGenerator' => 'enumGenerator',
            ],
            'Enum of integers given' => [
                'schema' => new Schema(
                    enum: [123, 234],
                ),
                'expectedSchemaGenerator' => 'enumGenerator',
            ],
            'Enum of mixed types given' => [
                'schema' => new Schema(
                    enum: ['value1', 123],
                ),
                'expectedSchemaGenerator' => 'enumClassGenerator',
            ],
        ];
    }
}
