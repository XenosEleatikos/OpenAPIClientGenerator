<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\EnumGeneratorTest\Schema;

enum EnumOfIntegers: int
{
    case CASE_1 = 1;
    case CASE_2 = 2;
    case CASE_3 = 3;
}
