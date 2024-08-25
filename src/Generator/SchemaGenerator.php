<?php

namespace OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Response;
use OpenApiClientGenerator\Model\OpenApi\Schema;
use OpenApiClientGenerator\Model\OpenApi\SchemaType;
use stdClass;

use function implode;
use function ucfirst;

readonly class SchemaGenerator extends AbstractGenerator
{
    private TypeHintGenerator $typeHintGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->typeHintGenerator = new TypeHintGenerator($config, $printer);
    }

    public function generate(OpenAPI $openAPI): void
    {
        foreach ($openAPI->components->schemas as $name => $schema) {
            $this->generateSchema($name, $schema, $openAPI);
        }
    }

    public static function createSchemaClassNameFromReferencePath(string $referencePath): string
    {
        $referencePath = explode('/', $referencePath);

        return array_pop($referencePath);
    }

    public function generateSchema(string $name, Schema $schema, OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Schema');
        $class = new ClassType(ucfirst($name));
        $this->addConstructor($class, $schema, $openAPI);
        $this->addFactory($class, $schema, $openAPI);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile('/src/Schema/' . ucfirst($name) . '.php', $file);
    }

    private function addConstructor(ClassType $class, Schema $schema, OpenAPI $openAPI): void
    {
        $constructor = $class->addMethod('__construct');

        foreach ($schema->properties->resolveProperties($openAPI) as $name => $property) {
            /** @var Schema $property */
            $constructor
                ->addPromotedParameter($name)
                ->setType($this->getTypeHint($property));
        }
    }

    private function addFactory(ClassType $class, Schema $schema, OpenAPI $openAPI): void
    {
        $factory = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('self');

        $factory->addParameter('data')
            ->setType(stdClass::class);

        $factory
            ->addBody('return new self(');
        foreach ($schema->properties->resolveProperties($openAPI) as $name => $property) {
            /** @var Schema $property */
            $factory
                ->addBody('    '.$name.': $data->' . $name.',');
        }

        $factory->addBody(');');
    }

    public function getTypeHint(Schema $schema): string
    {
        $typeHint = $this->typeHintGenerator->getReturnTypes($schema);

        return implode('|', $typeHint ?? []);
    }
}
