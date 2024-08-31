<?php

namespace OpenApiClientGenerator\Generator\SchemaGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\AbstractGenerator;
use OpenApiClientGenerator\Generator\SchemaGenerator;
use OpenApiClientGenerator\Generator\TypeHintGenerator;
use OpenApiClientGenerator\Model\OpenApi\Reference;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Schema;
use stdClass;

use function implode;
use function ucfirst;

readonly class ClassGenerator extends AbstractGenerator
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
        $class = new ClassType(ucfirst($name));
        $this->addConstructor($class, $schema, $openAPI, $name);
        $this->addFactory($class, $schema, $openAPI, $name);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile('/src/Schema/' . ucfirst($name) . '.php', $file);
    }

    private function addConstructor(ClassType $class, Schema $schema, OpenAPI $openAPI, string $schemaName): void
    {
        $constructor = $class->addMethod('__construct');

        foreach ($schema->properties as $name => $property) {
            $constructor
                ->addPromotedParameter($name)
                ->setType(
                    implode('|', $this->typeHintGenerator->getReturnTypes($property, $openAPI, $schemaName, $name))
                );
        }
    }

    private function addFactory(ClassType $class, Schema $schema, OpenAPI $openAPI, string $className): void
    {
        $factory = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('self');
        $factory->addParameter('data')
            ->setType(stdClass::class);
        $factory
            ->addBody('return new self(');

        foreach ($schema->properties as $propertyName => $propertySchemaOrReference) {
            list($propertyClassName, $propertySchema) = SchemaGenerator::createSchemaClassName($propertySchemaOrReference, $openAPI, $className, $propertyName);

            $factoryCall = match(SchemaGenerator::getPhpType($propertySchema)) {
                'object' => self::getFactoryCall($propertyClassName, $propertyName),
                'enum' => EnumGenerator::getFactoryCall($propertyClassName, $propertyName),
                'scalar' => self::getPropertyCall($propertyName),
            };

            $factory
                ->addBody('    '.$propertyName.': ' . $factoryCall.',');
        }

        $factory->addBody(');');
    }

    public static function getPropertyCall($propertyName): string
    {
        return '$data->' . $propertyName;
    }

    public static function getFactoryCall(string $propertyClassName, $propertyName): string
    {
        return $propertyClassName . '::make($data->' . $propertyName.')';
    }
}
