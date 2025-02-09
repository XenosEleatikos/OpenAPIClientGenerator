<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use ArrayObject;
use InvalidArgumentException;
use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function ucfirst;

class CollectionGenerator implements SchemaGeneratorInterface
{
    private ?SchemaGeneratorContainer $schemaGeneratorContainer = null;

    public function __construct(
        readonly private Config $config,
        readonly private Printer $printer,
    ) {
    }

    public function isResponsible(Schema $schema): bool
    {
        return $schema->type->contains(SchemaType::ARRAY);
    }

    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void
    {
        if (!$schema->type->contains(SchemaType::ARRAY)) {
            throw new InvalidArgumentException('Argument $schema of method ' . __METHOD__ . ' has to be an array.');
        }

        $namespace = new PhpNamespace($this->config->namespace . '\Schema');
        $class = new ClassType(ucfirst($name));
        $class->setExtends(ArrayObject::class);
        $class->setComment(
            '@extends ArrayObject<int, '
            . $this->getItemsTypes($schema->items, $openAPI, $name)
            . '>'
        );

        $this->addFactory(class: $class, schema: $schema, openAPI: $openAPI, className: $name);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Schema/' . ucfirst($name) . '.php', $file);
    }

    private function addFactory(ClassType $class, Schema $schema, OpenAPI $openAPI, string $className): void
    {
        $factory = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('self');
        $factory->addParameter('data')
            ->setType('array');

        $constructorArgumentLiteral = isset($schema->items)
            ? '\array_map(' . PHP_EOL
            . '        callback: fn(' . $this->getRawDataTypes($schema->items, $openAPI) . ' $item): '
            . $this->getItemsTypes($schema->items, $openAPI, $className)
            . ' => ' . $this->getContainer()->getFactoryCall(
                schemaOrReference: $schema->items,
                openAPI: $openAPI,
                parentClassName: $className,
                propertyName: 'items',
                parameter: '$item'
            ) . ', ' . PHP_EOL
            . '        array: $data' . PHP_EOL
            . '    )'
            : '$data';

        $factory->addBody(
            'return new self(' . $constructorArgumentLiteral . ');'
        );
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

    private function getItemsTypes(null|Reference|Schema $schema, OpenAPI $openAPI, string $className): string
    {
        return implode(
            separator: '|',
            array: array_map(
                callback: fn (string|FullyQualifiedClassName $type): string => $type instanceof FullyQualifiedClassName ? '\\' . $type : $type,
                array: $this->getContainer()->getReturnTypes(
                    schemaOrReference: $schema,
                    openAPI: $openAPI,
                    parentClassName: $className,
                    propertyName: 'items'
                )
            )
        );
    }

    private function getRawDataTypes(null|Reference|Schema $schema, OpenAPI $openAPI): string
    {
        return implode(
            separator: '|',
            array: $this->getContainer()->getRawDataTypes(
                schemaOrReference: $schema,
                openAPI: $openAPI,
            )
        );
    }

    public function getFactoryCall(string $propertyClassName, string $parameter): string
    {
        return $propertyClassName . '::make(' . $parameter . ')';
    }
}
