<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\EnumGeneratorTest\Schema;

enum EnumOfStrings: string
{
    case available = 'available';
    case pending = 'pending';
    case sold = 'sold';
}
