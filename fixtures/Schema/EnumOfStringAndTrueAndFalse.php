<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Schema;

class EnumOfStringAndTrueAndFalse
{
    public string|bool $value;

    private function __construct(string|bool $value)
    {
        $this->value = $value;
    }

    public static function from(string|bool $value): self
    {
        return match ($value) {
            'one' => self::caseOne(),
            true => self::caseTrue(),
            false => self::caseFalse(),
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

    public static function caseFalse(): self
    {
        static $value = null;
        return $value ??= new self(false);
    }
}
