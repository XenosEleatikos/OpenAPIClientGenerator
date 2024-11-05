<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTestHelper;

use ReflectionClass;
use ReflectionMethod;

use function array_filter;
use function array_map;
use function array_values;
use function in_array;

class Reflection
{
    /**
     * @param string[] $exclude
     * @return string[]
     */
    public static function getMethodNames(ReflectionClass $reflectionClass, array $exclude = []): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn (ReflectionMethod $method): string => $method->getName(),
                    $reflectionClass->getMethods()
                ),
                fn (string $methodName) => !in_array($methodName, $exclude),
            )
        );
    }
}
