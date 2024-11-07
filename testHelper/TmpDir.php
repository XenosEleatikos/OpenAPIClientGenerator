<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTestHelper;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use ReflectionClass;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;

use function array_filter;
use function array_values;
use function class_exists;
use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function realpath;
use function scandir;
use function str_replace;
use function sys_get_temp_dir;

class TmpDir
{
    const string ROOT_NAMESPACE = 'Xenos\OpenApiClientGeneratorFixture';

    public readonly string $namespace;
    public readonly string $path;

    public function __construct(string $namespace = '') {
        $this->namespace = self::ROOT_NAMESPACE . (!empty($namespace) ? '\\' : '') . $namespace;
        $this->path = self::createTmpDir();
    }

    private static function createTmpDir(): string
    {
        return sys_get_temp_dir() . '/openApiClient/' . \microtime(true);
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
            namespace: $this->namespace,
            directory: $this->path
        );
    }

    public function reflectGeneratedClass(string $class): ReflectionClass
    {
        $this->require($class);

        return new ReflectionClass($this->namespace . '\\' . $class);
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->path . '/src/' . $relativePath;
    }

    public function list(string $directoryPath): array
    {
        $directoryPath = $this->getAbsolutePath($directoryPath);

        if (!is_dir($directoryPath)) {
            return [];
        }

        return array_values(
            array: array_filter(
                array: scandir($directoryPath),
                callback: fn(string $file): bool => $file !== '.' && $file !== '..'
            )
        );
    }

    /** @return class-string */
    public function getFullyQualifiedClassName(string $relativeClassName): string
    {
        return $this->namespace . '\\' . $relativeClassName;
    }

    public function addClass(ClassType $classType, string $namespace)
    {
        $file = new PhpFile();
        $namespace = new PhpNamespace($this->namespace . '\\' . $namespace);
        $namespace->add($classType);
        $file->addNamespace($namespace);
        $printer = new PsrPrinter();

        $path = $this->getAbsolutePath(
            str_replace(
                search: '\\',
                replace: DIRECTORY_SEPARATOR,
                subject: $classType->getName()
            )
        ) . '.php';
        $this->filePutContentsSave(
            $path,
            $printer->printFile($file)
        );

        $this->require($classType->getName());
    }

    private function filePutContentsSave(string $filePath, string $data, int $flags = 0): int|false
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }
        }

        return file_put_contents($filePath, $data, $flags);
    }

    private static function getPath(string $class): string
    {
        return '/src/' . self::namespaceToDir($class) . '.php';
    }

    /**
     * @param string $class
     * @return void
     */
    public function require(string $class): void
    {
        if (!class_exists($this->namespace . '\\' . $class)) {
            require_once realpath($this->path . self::getPath($class));
        }
    }
}
