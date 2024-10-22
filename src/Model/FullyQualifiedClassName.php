<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Model;

use InvalidArgumentException;

use function preg_match;

class FullyQualifiedClassName
{
    private string $fqcn;
    private string $namespace;
    private string $className;

    public function __construct(string $fqcn)
    {
        $this->validateFQCN($fqcn);
        $this->fqcn = $fqcn;

        $lastBackslash = strrpos($fqcn, '\\');
        $this->namespace = $lastBackslash === false ? '' : substr($fqcn, 0, $lastBackslash);
        $this->className = substr($fqcn, $lastBackslash !== false ? $lastBackslash + 1 : 0);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    private function validateFQCN(string $fqcn): void
    {
        if (!$this->isValidFQCN($fqcn)) {
            throw new InvalidArgumentException("Invalid fully qualified class name: $fqcn");
        }
    }

    private function isValidFQCN(string $fqcn): bool
    {
        return preg_match('/^(\\\\?[a-zA-Z_][a-zA-Z0-9_]*(\\\\[a-zA-Z_][a-zA-Z0-9_]*)*)$/', $fqcn) === 1;
    }

    public function __toString(): string
    {
        return $this->fqcn;
    }
}
