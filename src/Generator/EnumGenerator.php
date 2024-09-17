<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use InvalidArgumentException;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApi\Model\Schema;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

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

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Schema/' . ucfirst($name) . '.php', $file);
    }

    public static function getFactoryCall(string $propertyClassName, string $propertyName): string
    {
        return $propertyClassName . '::from($data->' . $propertyName.')';
    }
}
