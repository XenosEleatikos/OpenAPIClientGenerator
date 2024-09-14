<?php

namespace OpenApiClientGenerator\Generator\SchemaGenerator;

use InvalidArgumentException;
use LogicException;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\AbstractGenerator;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\Schema;

use function ucfirst;

readonly class EnumGenerator extends AbstractGenerator
{
    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
    }

    public function generateSchema(string $name, Schema $schema): void
    {
        if (!$schema->isEnumOfStrings() && !$schema->isEnumOfIntegers()) {
            throw new InvalidArgumentException('Argument $schema of method ' . __METHOD__ . ' has to be an enum of strings or an enum of integers.');
        }

        $namespace = new PhpNamespace($this->config->namespace . '\Schema');
        $class = new EnumType(ucfirst($name));

        if ($schema->isEnumOfStrings()) {
            foreach ($schema->enum as $enum) {
                /** @var string $enum */
                $class
                    ->addCase($enum)
                    ->setValue($enum);
            }
        } elseif ($schema->isEnumOfIntegers()) {
            foreach ($schema->enum as $enum) {
                /** @var int $enum */
                $class
                    ->addCase('CASE_' . $enum)
                    ->setValue($enum);
            }
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
