<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use stdClass;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function array_map;
use function implode;
use function ucfirst;
use function array_keys;

use const DIRECTORY_SEPARATOR;

class ClassGenerator implements SchemaGeneratorInterface
{
    private ?SchemaGeneratorContainer $schemaGeneratorContainer = null;

    public function __construct(
        private readonly SchemaClassNameGenerator $schemaClassNameGenerator,
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
        $this->addConstructor(class: $class, schema: $schema, openAPI: $openAPI, className: $name);
        $this->addFactory(class: $class, schema: $schema, openAPI: $openAPI, className: $name);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile(
            path: $this->config->directory
            . DIRECTORY_SEPARATOR
            . 'src'
            . DIRECTORY_SEPARATOR
            . 'Schema'
            . DIRECTORY_SEPARATOR
            . ucfirst($name)
            . '.php',
            file: $file
        );
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

        if ($schema->additionalProperties !== false) {
            $constructor
                ->addPromotedParameter('additionalProperties')
                ->setType(
                    $additionalPropertiesClassName = $this->config->namespace . '\Schema\\' . $this->schemaClassNameGenerator
                        ->createSchemaClassNameFromParentClassNameAndProperty(
                            parentClassName: $className,
                            propertyName: 'additionalProperties'
                        )
                )
                ->setDefaultValue(new Literal('new \\' . $additionalPropertiesClassName . '()'));
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
            $factoryCall = $this->getContainer()->getFactoryCall(
                schemaOrReference: $propertySchemaOrReference,
                openAPI: $openAPI,
                parentClassName: $className,
                propertyName: $propertyName,
                parameter: '$data->' . $propertyName
            );

            $factory
                ->addBody('    '.$propertyName.': ' . $factoryCall.',');
        }

        if ($schema->additionalProperties !== false) {
            $objectGetVars = '\get_object_vars($data)';

            $additionalPropertiesArray = $schema->properties->count() === 0
                ? $objectGetVars
                : $this->generateArrayFilter(
                    arrayLiteral: $objectGetVars,
                    filterCallbackLiteral: $this->generateFilterCallback($schema),
                    schema: $schema
                );

            $factory->addBody(
                code: '    additionalProperties: ' . $this->getContainer()->collectionGenerator->getFactoryCall(
                    propertyClassName: '\\' . $this->config->namespace . '\Schema\\' . $this->schemaClassNameGenerator
                            ->createSchemaClassNameFromParentClassNameAndProperty(
                                parentClassName: $className,
                                propertyName: 'additionalProperties'
                            ),
                    parameter: $additionalPropertiesArray
                )
            );
        }

        $factory->addBody(');');
    }

    private function generateFilterCallback(Schema $schema): string
    {
        return 'fn(string $key): bool => !\in_array($key, [' . PHP_EOL
            . '            ' . implode(
                separator: ', ',
                array: array_map(
                    callback: fn (string $key): string => '\'' . $key . '\'',
                    array: array_keys($schema->properties->getArrayCopy())
                )
            ) . PHP_EOL
        . '        ]),';
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

    public function generateArrayFilter(string $arrayLiteral, string $filterCallbackLiteral, Schema $schema): string
    {
        return '\array_filter(' . PHP_EOL
            . '        array: ' . $arrayLiteral . ',' . PHP_EOL
            . '        callback: ' . $filterCallbackLiteral . PHP_EOL
            . '        mode: \ARRAY_FILTER_USE_KEY' . PHP_EOL
            . '    )';
    }
}
