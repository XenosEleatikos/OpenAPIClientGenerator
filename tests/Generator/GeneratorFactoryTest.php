<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator;

use PHPUnit\Framework\TestCase;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Generator;
use Xenos\OpenApiClientGenerator\Generator\GeneratorFactory;

class GeneratorFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $generator = GeneratorFactory::make(
            new Config(
                namespace: 'PetShopApi',
                directory: '/tmp/pet-shop-api',
            )
        );

        self::assertInstanceOf(Generator::class, $generator);
    }
}
