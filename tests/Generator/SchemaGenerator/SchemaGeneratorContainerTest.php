<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\SchemaGenerator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\ContainerAwareInterface;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorInterface;

class SchemaGeneratorContainerTest extends TestCase
{
    /**
     * @param ?int $expected           Key of the injected {@see SchemaGeneratorInterface} which will be created due to 2nd parameter
     *                                 null, if null is expected as return value
     * @param bool[] $responsibilities For each value a mock instance of {@see SchemaGeneratorInterface} will be injected
     *                                 into the container and the value itself will be returned by {@see SchemaGeneratorInterface::isResponsible()}
     */
    #[DataProvider('provideDataForTestGetSchemaGenerator')]
    public function testGetSchemaGenerator(
        ?int $expected,
        array $responsibilities
    ): void {
        $schema = new Schema();

        foreach ($responsibilities as $responsibility) {
            /** @var MockObject&SchemaGeneratorInterface $generator */
            $generator = $this->createMock(SchemaGeneratorInterface::class);
            $generator->method('isResponsible')->with($schema)->willReturn($responsibility);
            $generators[] = $generator;
        }

        $schemaGeneratorContainer = new SchemaGeneratorContainer(
            config: $this->createStub(Config::class),
            schemaClassNameGenerator: $this->createStub(SchemaClassNameGenerator::class)
        );

        $schemaGeneratorContainer->add(...$generators ?? []);

        $schemaGenerator = $schemaGeneratorContainer->getSchemaGenerator($schema);

        self::assertSame(
            isset($expected)
                ? $generators[$expected] ?? null
                : null,
            $schemaGenerator
        );
    }

    /** @return array<string, array<string, array<int, bool>|int|null>> */
    public static function provideDataForTestGetSchemaGenerator(): array
    {
        return [
            'No generator' => [
                'expected' => null,
                'responsibilities' => [],
            ],
            'One responsible generator' => [
                'expected' => 0,
                'responsibilities' => [true],
            ],
            'One not responsible generator' => [
                'expected' => null,
                'responsibilities' => [false],
            ],
            'Two generators, first responsible' => [
                'expected' => 0,
                'responsibilities' => [true, false],
            ],
            'Two generators, second responsible' => [
                'expected' => 1,
                'responsibilities' => [false, true],
            ],
            'Two generators, both responsible' => [
                'expected' => 0,
                'responsibilities' => [true, true],
            ],
        ];
    }

    public function testGetSchemaGeneratorSetsSelfToContainerAwareInstances(): void
    {
        $schemaGeneratorContainer = new SchemaGeneratorContainer(
            config: $this->createStub(Config::class),
            schemaClassNameGenerator: $this->createStub(SchemaClassNameGenerator::class),
        );

        /** @var MockObject&SchemaGeneratorInterface&ContainerAwareInterface $generator */
        $generator = $this->createMockForIntersectionOfInterfaces([SchemaGeneratorInterface::class, ContainerAwareInterface::class]);
        $generator->expects(self::once())
            ->method('setContainer')
            ->with($schemaGeneratorContainer);

        $schemaGeneratorContainer->add($generator);
    }
}
