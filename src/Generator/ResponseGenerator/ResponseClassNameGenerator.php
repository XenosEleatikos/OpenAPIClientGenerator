<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ResponseGenerator;

use Error;
use InvalidArgumentException;
use Xenos\OpenApi\Model\AbstractComponentsSubList;
use Xenos\OpenApi\Model\Method;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function array_pop;
use function explode;
use function is_null;
use function is_numeric;
use function preg_match;
use function preg_replace_callback;
use function strtoupper;
use function ucfirst;
use function var_export;

readonly class ResponseClassNameGenerator
{
    public function __construct(
        private Config $config,
        private MethodNameGenerator $methodNameGenerator,
    ) {
    }

    public function fromOperation(
        Method $method,
        string $endpoint,
        Operation $operation,
        string $statusCode
    ): FullyQualifiedClassName {
        return new FullyQualifiedClassName(
            fqcn: $this->config->namespace
            . '\Response\\'
            . ucfirst($this->methodNameGenerator->generateMethodName($method, $endpoint, $operation))
            . ucfirst($statusCode) // might be "default"
            . 'Response'
        );
    }

    public function fromReferencePath(string $referencePath): FullyQualifiedClassName
    {
        $referencePath = explode('/', $referencePath);

        return $this->fromComponentsKey(array_pop($referencePath));
    }

    public function fromComponentsKey(string $componentsKey): FullyQualifiedClassName
    {
        if (!preg_match(AbstractComponentsSubList::KEY_PATTERN, $componentsKey)) {
            throw new InvalidArgumentException('Component key must be a string matching the regular expression "' . AbstractComponentsSubList::KEY_PATTERN . '", ' . var_export($componentsKey, true) . ' given.');
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

        if (is_numeric($className)) {
            $className = 'Response' . $className;
        }

        return new FullyQualifiedClassName($this->config->namespace . '\Response\\' . ucfirst($className));
    }
}
