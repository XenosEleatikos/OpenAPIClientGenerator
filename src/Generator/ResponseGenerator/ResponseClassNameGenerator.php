<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ResponseGenerator;

use Error;
use InvalidArgumentException;
use Xenos\OpenApi\Model\AbstractComponentsSubList;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function array_map;
use function array_pop;
use function explode;
use function implode;
use function is_null;
use function preg_match;
use function preg_replace_callback;
use function strtolower;
use function strtoupper;
use function ucfirst;

class ResponseClassNameGenerator
{
    public function __construct(
        private Config $config
    ) {
    }

    public function createResponseClassName(string $method, string $endpoint, Operation $operation, string $statusCode): FullyQualifiedClassName
    {
        if (empty($operation->operationId)) {
            $operationName = strtolower($method) . $this->convertEndpointToCamelCase($endpoint);
        } else {
            $operationName = $operation->operationId;
        }

        return new FullyQualifiedClassName($this->config->namespace . '\Response\\' . ucfirst($operationName . $statusCode . 'Response'));
    }

    public function createResponseClassNameFromReferencePath(string $referencePath): FullyQualifiedClassName
    {
        $referencePath = explode('/', $referencePath);

        return $this->createResponseClassNameFromComponentsKey(array_pop($referencePath));
    }

    public function createResponseClassNameFromComponentsKey(string $componentsKey): FullyQualifiedClassName
    {
        if (!preg_match(AbstractComponentsSubList::KEY_PATTERN, $componentsKey)) {
            throw new InvalidArgumentException('Component key must be a string matching the regular expression "^[a-zA-Z0-9._-]+$", ' . var_export($componentsKey, true) . ' given.');
        }

        // Removes some special chars which are allowed in component keys and sets the following char to upper case
        $className = preg_replace_callback(
            pattern: '/[.\-_](.)/',
            callback: fn (array $matches): string => strtoupper($matches[1]),
            subject: $componentsKey
        );

        if (is_null($className)) {
            throw new Error();
        }

        return new FullyQualifiedClassName($this->config->namespace . '\Response\\' . ucfirst($className));
    }

    private function convertEndpointToCamelCase(string $endpoint): string
    {
        return implode(
            separator: '',
            array: array_map(
                fn ($part) => ucfirst($part),
                explode('/', $endpoint)
            )
        );
    }
}
