<?php

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use stdClass;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\TypeHintGenerator;

use function implode;
use function ucfirst;

readonly class ClassGenerator extends AbstractGenerator
{
    private TypeHintGenerator $typeHintGenerator;
    private SchemaClassNameGenerator $schemaClassNameGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->typeHintGenerator = new TypeHintGenerator($config, $printer);
        $this->schemaClassNameGenerator = new SchemaClassNameGenerator();
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

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Schema/' . ucfirst($name) . '.php', $file);
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
            list($propertyClassName, $propertySchema) = $this->schemaClassNameGenerator->createSchemaClassName($propertySchemaOrReference, $openAPI, $className, $propertyName);

            $factoryCall = match(SchemaGenerator::getPhpType($propertySchema)) {
                'object' => self::getFactoryCall($propertyClassName, $propertyName),
                'enum' => EnumGenerator::getFactoryCall($propertyClassName, $propertyName),
                default => self::getPropertyCall($propertyName),
            };

            $factory
                ->addBody('    '.$propertyName.': ' . $factoryCall.',');
        }

        $factory->addBody(');');
    }

    public static function getPropertyCall(string $propertyName): string
    {
        return '$data->' . $propertyName;
    }

    public static function getFactoryCall(string $propertyClassName, string $propertyName): string
    {
        return $propertyClassName . '::make($data->' . $propertyName.')';
    }
}
