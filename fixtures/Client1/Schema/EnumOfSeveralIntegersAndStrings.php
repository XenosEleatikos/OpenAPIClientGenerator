<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Client1\Schema;

class EnumOfSeveralIntegersAndStrings
{
    public int|string $value;

    private function __construct(int|string $value)
    {
        $this->value = $value;
    }

    public static function from(int|string $value): self
    {
        return match ($value) {
            1 => self::case1(),
            2 => self::case2(),
            'three' => self::caseThree(),
            'four' => self::caseFour(),
            default => throw new \ValueError($value . ' is not a valid backing value for enum ' . self::class),
        };
    }

    public static function case1(): self
    {
        static $value = null;
        return $value ??= new self(1);
    }

    public static function case2(): self
    {
        static $value = null;
        return $value ??= new self(2);
    }

    public static function caseThree(): self
    {
        static $value = null;
        return $value ??= new self('three');
    }

    public static function caseFour(): self
    {
        static $value = null;
        return $value ??= new self('four');
    }
}
