<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTestHelper;

use ReflectionClass;
use ReflectionMethod;

use ReflectionParameter;
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
            array: array_filter(
                array_map(
                    callback: fn (ReflectionMethod $method): string => $method->getName(),
                    array: $reflectionClass->getMethods()
                ),
                callback: fn (string $methodName) => !in_array($methodName, $exclude),
            )
        );
    }

    /** @return array<string, string> Parameter names as keys and types as value */
    public static function getParameters(ReflectionMethod $reflectionMethod): array
    {
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $reflectionParameters[$reflectionParameter->getName()] = (string)$reflectionParameter->getType();
        }

        return $reflectionParameters ?? [];
    }
}
