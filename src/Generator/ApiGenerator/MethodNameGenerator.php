<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ApiGenerator;

use Xenos\OpenApi\Model\Method;
use Xenos\OpenApi\Model\Operation;

use function array_map;
use function explode;
use function implode;
use function preg_match;
use function strtolower;
use function trim;
use function ucfirst;

class MethodNameGenerator
{
    public static function generateMethodName(Method $method, string $path, Operation $operation): string
    {
        if (!empty($operation->operationId)) {
            return $operation->operationId;
        }

        return self::getMethodNameFromMethodAndPath($method, $path);
    }

    private static function getMethodNameFromMethodAndPath(Method $method, string $path): string
    {
        $path = trim($path, '/');

        $segments = explode('/', $path);
        $segments = array_map(
            fn (string $segment): string => preg_match('/^\{(\w+)\}$/', $segment, $matches)
                ? 'By' . ucfirst($matches[1])
                : ucfirst($segment),
            $segments
        );

        return strtolower($method->lowerCase()) . implode($segments);
    }
}
