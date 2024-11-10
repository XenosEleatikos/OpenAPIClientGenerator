<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use stdClass;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function implode;
use function ucfirst;

class ClassGenerator implements SchemaGeneratorInterface, ContainerAwareInterface
{
    private ?SchemaGeneratorContainer $schemaGeneratorContainer = null;

    public function __construct(
        private readonly Config $config,
        private readonly Printer $printer,
    ) {
    }

    public function isResponsible(Schema $schema): bool
    {
        return $schema->type->contains(SchemaType::OBJECT);
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

    private function addConstructor(ClassType $class, Schema $schema, OpenAPI $openAPI, string $className): void
    {
        $constructor = $class->addMethod('__construct');

        foreach ($schema->properties as $propertyName => $schemaOrReference) {
            $constructor
                ->addPromotedParameter($propertyName)
                ->setType(
                    implode(
                        separator: '|',
                        array: $this->getContainer()->getReturnTypes(
                            schemaOrReference: $schemaOrReference,
                            openAPI: $openAPI,
                            parentClassName: $className,
                            propertyName: $propertyName
                        )
                    )
                );
        }
    }

    private function addFactory(ClassType $class, Schema $schema, OpenAPI $openAPI, string $className): void
    {
        $factory = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('self');
        $factory->addParameter('data')
            ->setType(stdClass::class); //  @todo Might be other types
        $factory
            ->addBody('return new self(');

        foreach ($schema->properties as $propertyName => $propertySchemaOrReference) {
            $returnTypes = $this->getContainer()->getReturnTypes(
                schemaOrReference: $propertySchemaOrReference,
                openAPI: $openAPI,
                parentClassName: $className,
                propertyName: $propertyName
            );

            /** @var Schema $propertySchema */
            $propertySchema = $propertySchemaOrReference instanceof Reference
                ? $openAPI->resolveReference($propertySchemaOrReference)
                : $propertySchemaOrReference;

            $schemaGenerator = $this->getContainer()->getSchemaGenerator($propertySchema);
            $factoryCall = isset($schemaGenerator)
                ? $schemaGenerator->getFactoryCall(
                    propertyClassName: $returnTypes[0]->getClassName(),
                    parameter: '$data->' . $propertyName
                )
                : self::getPropertyCall($propertyName);

            $factory
                ->addBody('    '.$propertyName.': ' . $factoryCall.',');
        }

        $factory->addBody(');');
    }

    public static function getPropertyCall(string $propertyName): string
    {
        return '$data->' . $propertyName;
    }

    public function getFactoryCall(string $propertyClassName, string $parameter): string
    {
        return $propertyClassName . '::make(' . $parameter . ')';
    }

    public function setContainer(SchemaGeneratorContainer $schemaGeneratorContainer): void
    {
        $this->schemaGeneratorContainer = $schemaGeneratorContainer;
    }

    private function getContainer(): SchemaGeneratorContainer
    {
        return $this->schemaGeneratorContainer
            ?? throw new LogicException('SchemaGeneratorContainer is not set.');
    }
}
