<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Schema;

class EnumOfStringAndTrue
{
    public string|true $value;

    private function __construct(string|true $value)
    {
        $this->value = $value;
    }

    public static function from(string|true $value): self
    {
        return match ($value) {
            'one' => self::caseOne(),
            true => self::caseTrue(),
            default => throw new \ValueError($value . ' is not a valid backing value for enum ' . self::class),
        };
    }

    public static function caseOne(): self
    {
        static $value = null;
        return $value ??= new self('one');
    }

    public static function caseTrue(): self
    {
        static $value = null;
        return $value ??= new self(true);
    }
}
