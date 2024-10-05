<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixtureTest\Client1\Schema;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ValueError;
use Xenos\OpenApiClientGeneratorFixture\Client1\Schema\EnumOfSeveralIntegersAndStrings;

class EnumOfSeveralIntegersAndStringsTest extends TestCase
{
    public function testFactoryCreatesInstance(): EnumOfSeveralIntegersAndStrings
    {
        $enum = EnumOfSeveralIntegersAndStrings::case1();
        self::assertInstanceOf(EnumOfSeveralIntegersAndStrings::class, $enum);

        return $enum;
    }

    #[Depends('testFactoryCreatesInstance')]
    public function testFactoryCreatesSingleton(EnumOfSeveralIntegersAndStrings $enum): EnumOfSeveralIntegersAndStrings
    {
        self::assertSame($enum, EnumOfSeveralIntegersAndStrings::case1());

        return $enum;
    }

    #[Depends('testFactoryCreatesInstance')]
    public function testEnumHasRightValue(EnumOfSeveralIntegersAndStrings $enum): EnumOfSeveralIntegersAndStrings
    {
        self::assertSame(1, $enum->value);

        return $enum;
    }

    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith(['three'])]
    #[TestWith(['four'])]
    public function testFrom(int|string $value): void
    {
        $enum = EnumOfSeveralIntegersAndStrings::from($value);
        self::assertSame($value, $enum->value);
    }

    public function testFromThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('3 is not a valid backing value for enum Xenos\OpenApiClientGeneratorFixture\Client1\Schema\EnumOfSeveralIntegersAndStrings');

        EnumOfSeveralIntegersAndStrings::from(3);
    }
}
