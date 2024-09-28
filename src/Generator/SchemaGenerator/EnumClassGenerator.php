<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use InvalidArgumentException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\AbstractGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function get_debug_type;
use function implode;
use function in_array;
use function str_replace;
use function ucfirst;
use function var_export;

readonly class EnumClassGenerator extends AbstractGenerator implements SchemaGeneratorInterface
{
    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
    }

    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void
    {
        if (!$schema->isEnumOfScalarValues()) {
            throw new InvalidArgumentException('Argument $schema of method ' . __METHOD__ . ' has to be an enum of scalar values.');
        }

        $namespace = new PhpNamespace($this->config->namespace . '\Schema');
        $class = new ClassType(ucfirst($name));

        /** @var array<int|float|string|bool> $enum */
        $enum = $schema->enum;
        $types = self::getTypes($enum);
        $typeHint = implode('|', $types);
        $class
            ->addProperty('value')
            ->setType($typeHint);

        $class
            ->addMethod('__construct')
            ->setPrivate()
            ->setBody('$this->value = $value;')
            ->addParameter('value')
            ->setType($typeHint);

        $factory = $class->addMethod('from');
        $factory
            ->setStatic()
            ->setReturnType('self')
            ->addParameter('value')
            ->setType($typeHint);

        $factory->addBody('return match ($value) {');
        foreach ($enum as $value) {
            $factory->addBody('    ' . var_export($value, true) . ' => self::' . self::getFactoryName($value) . '(),');
        }
        $factory->addBody('    default => throw new \ValueError($value . \' is not a valid backing value for enum \' . self::class),');
        $factory->addBody('};');

        foreach ($enum as $value) {
            $class
                ->addMethod(self::getFactoryName($value))
                ->setStatic()
                ->setReturnType('self')
                ->addBody('static $value = null;')
                ->addBody('return $value ??= new self(' . var_export($value, true) . ');');
        }

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Schema/' . ucfirst($name) . '.php', $file);
    }

    private static function getFactoryName(int|float|string|bool $value): string
    {
        if ($value === true) {
            $valueName = 'true';
        } elseif ($value === false) {
            $valueName = 'false';
        } else {
            $valueName = (string) $value;
        }

        return 'case' . ucfirst(str_replace('.', '_', $valueName));
    }

    /**
     * @param array<int|float|string|bool> $enum
     * @return string[]
     */
    private static function getTypes(array $enum): array
    {
        $types = array_unique(
            array_map(
                function ($value) {
                    if ($value === true) {
                        return 'true';
                    } elseif ($value === false) {
                        return 'false';
                    }
                    return get_debug_type($value);
                },
                $enum
            )
        );

        if (in_array('true', $types) && in_array('false', $types)) {
            $types = array_filter($types, function ($type) {
                return $type !== 'true' && $type !== 'false';
            });
            $types[] = 'bool';
        }

        return array_values($types);
    }

    public static function getFactoryCall(string $propertyClassName, string $propertyName): string
    {
        return $propertyClassName . '::from($data->' . $propertyName.')';
    }
}
