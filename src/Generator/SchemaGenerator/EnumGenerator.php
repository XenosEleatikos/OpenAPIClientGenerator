<?php

namespace OpenApiClientGenerator\Generator\SchemaGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\AbstractGenerator;
use OpenApiClientGenerator\Generator\TypeHintGenerator;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Schema;
use stdClass;

use function implode;
use function ucfirst;

readonly class EnumGenerator extends AbstractGenerator
{
    private TypeHintGenerator $typeHintGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->typeHintGenerator = new TypeHintGenerator($config, $printer);
    }

    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Schema');
        $class = new EnumType(ucfirst($name));

        foreach ($schema->enum as $enum) {
            $class
                ->addCase($enum)
                ->setValue($enum);
        }

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile('/src/Schema/' . ucfirst($name) . '.php', $file);
    }

    public static function getFactoryCall(string $propertyClassName, string $propertyName): string
    {
        return $propertyClassName . '::from($data->' . $propertyName.')';
    }
}
