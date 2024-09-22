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

use function array_map;
use function array_unique;
use function implode;
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

        $types = array_unique(array_map('\get_debug_type', $schema->enum));
        $typeHint = implode('|', $types);
        $class
            ->addProperty('value')
            ->setPrivate()
            ->setType($typeHint);

        $class
            ->addMethod('__construct')
            ->setPrivate()
            ->setBody('$this->value = $value;')
            ->addParameter('value')
            ->setType($typeHint);

        /** @var int|float|string $value */
        foreach ($schema->enum as $value) {
            $class
                ->addMethod('case' . ucfirst(str_replace('.', '_', (string)$value)))
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

    public static function getFactoryCall(string $propertyClassName, string $propertyName): string
    {
        return $propertyClassName . '::from($data->' . $propertyName.')';
    }
}
