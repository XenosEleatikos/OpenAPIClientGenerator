<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client1\Schema;

class EnumOfIntegerFloatAndString
{
    public int|float|string $value;

    private function __construct(int|float|string $value)
    {
        $this->value = $value;
    }

    public static function from(int|float|string $value): self
    {
        return match ($value) {
            1 => self::case1(),
            2.34 => self::case2_34(),
            'three' => self::caseThree(),
            default => throw new \ValueError($value . ' is not a valid backing value for enum ' . self::class),
        };
    }

    public static function case1(): self
    {
        static $value = null;
        return $value ??= new self(1);
    }

    public static function case2_34(): self
    {
        static $value = null;
        return $value ??= new self(2.34);
    }

    public static function caseThree(): self
    {
        static $value = null;
        return $value ??= new self('three');
    }
}
