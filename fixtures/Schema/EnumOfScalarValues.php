<?php

declare(strict_types=1);

namespace OpenApiClientGeneratorFixture\Schema;

class EnumOfScalarValues
{
    private int|float|string $value;

    private function __construct(int|float|string $value)
    {
        $this->value = $value;
    }

    public function case1(): self
    {
        static $value = null;
        return $value ??= new self(1);
    }

    public function case2_34(): self
    {
        static $value = null;
        return $value ??= new self(2.34);
    }

    public function caseThree(): self
    {
        static $value = null;
        return $value ??= new self('three');
    }
}
