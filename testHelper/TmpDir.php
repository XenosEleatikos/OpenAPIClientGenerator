<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTestHelper;

use ReflectionClass;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;

use function file_get_contents;
use function realpath;
use function str_replace;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function time;

class TmpDir
{
    const string ROOT_NAMESPACE = 'Xenos\OpenApiClientGeneratorFixture\\';

    public readonly string $namespace;
    public readonly string $path;

    public function __construct(string $namespace) {
        $this->namespace = self::ROOT_NAMESPACE . $namespace;
        $this->path = self::createTmpDir();
    }

    private static function createTmpDir(): string
    {
        return sys_get_temp_dir() . '/openApiClient/' . time();
    }

    private static function namespaceToDir(string $class): string
    {
        return str_replace(search: '\\', replace: DIRECTORY_SEPARATOR, subject: $class);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function makeConfig(): Config
    {
        return new Config(
            namespace: $this->getTemporaryModifiedNamespace(),
            directory: $this->path
        );
    }

    public function reflectGeneratedClass(string $class): ReflectionClass
    {
        require_once realpath($this->path . self::getPath($class));
        return new ReflectionClass($this->getTemporaryModifiedNamespace() . '\\' . $class);
    }

    public function reflectFixture(string $class): ReflectionClass
    {
        return new ReflectionClass($this->namespace . '\\' . $class);
    }

    public function getFixtureFile(string $relativePath): string
    {
        return file_get_contents($this->getFixturesMainDir() . DIRECTORY_SEPARATOR . $relativePath);
    }

    public function getGeneratedFile(string $relativePath): string
    {
        return $this->removeTemporaryNamespaceModifier(
            file_get_contents($this->path . '/src/' . $relativePath)
        );
    }

    public function getGeneratedFilePath(string $relativePath): string
    {
        return $this->path . '/src/' . $relativePath;
    }

    public function removeTemporaryNamespaceModifier(string $code): string
    {
        return str_replace(
            search: $this->getTemporaryModifiedNamespace(),
            replace: $this->namespace,
            subject: $code
        );
    }

    private function getTemporaryModifiedNamespace(): string
    {
        return $this->namespace . 'x';
    }

    private static function getPath(string $class): string
    {
        return '/src/' . self::namespaceToDir($class) . '.php';
    }

    private function getFixturesMainDir(): string
    {
        return __DIR__ . '/../fixtures/' . substr(string: self::namespaceToDir($this->namespace), offset: strlen(self::ROOT_NAMESPACE));
    }
}
