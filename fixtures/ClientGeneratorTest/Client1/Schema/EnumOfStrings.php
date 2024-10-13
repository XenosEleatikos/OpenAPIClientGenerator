<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\ClientGeneratorTest\Client1\Schema;

enum EnumOfStrings: string
{
    case available = 'available';
    case pending = 'pending';
    case sold = 'sold';
}
