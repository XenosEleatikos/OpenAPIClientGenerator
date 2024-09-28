<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Schema;

class EnumOfStringAndFalse
{
    public string|false $value;

    private function __construct(string|false $value)
    {
        $this->value = $value;
    }

    public static function from(string|false $value): self
    {
        return match ($value) {
            'one' => self::caseOne(),
            false => self::caseFalse(),
            default => throw new \ValueError($value . ' is not a valid backing value for enum ' . self::class),
        };
    }

    public static function caseOne(): self
    {
        static $value = null;
        return $value ??= new self('one');
    }

    public static function caseFalse(): self
    {
        static $value = null;
        return $value ??= new self(false);
    }
}
