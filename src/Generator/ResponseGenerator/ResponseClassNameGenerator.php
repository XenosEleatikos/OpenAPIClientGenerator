<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ResponseGenerator;

use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function array_map;
use function array_pop;
use function explode;
use function implode;
use function strtolower;
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
        return new FullyQualifiedClassName($this->config->namespace . '\Response\\' . ucfirst($componentsKey));
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
