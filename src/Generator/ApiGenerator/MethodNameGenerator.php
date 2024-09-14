<?php

namespace OpenApiClientGenerator\Generator\ApiGenerator;

use OpenApiClientGenerator\Model\OpenApi\Operation;

use function array_map;
use function explode;
use function implode;
use function preg_match;
use function trim;
use function ucfirst;

class MethodNameGenerator
{
    public static function generateMethodName(string $method, string $path, Operation $operation): string
    {
        if (!empty($operation->operationId)) {
            return $operation->operationId;
        }

        return self::getMethodNameFromMethodAndPath($method, $path);
    }

    private static function getMethodNameFromMethodAndPath(string $method, string $path): string
    {
        $path = trim($path, '/');

        $segments = explode('/', $path);
        $segments = array_map(
            fn (string $segment): string => preg_match('/^\{(\w+)\}$/', $segment, $matches)
                ? 'By' . ucfirst($matches[1])
                : ucfirst($segment),
            $segments
        );

        return $method . implode($segments);
    }
}
