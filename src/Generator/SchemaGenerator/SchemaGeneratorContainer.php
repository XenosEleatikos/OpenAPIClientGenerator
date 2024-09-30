<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

readonly class SchemaGeneratorContainer
{
    private ClassGenerator $classGenerator;
    private EnumGenerator $enumGenerator;
    private EnumClassGenerator $enumClassGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        $this->classGenerator = new ClassGenerator($config, $printer, $this);
        $this->enumGenerator = new EnumGenerator($config, $printer);
        $this->enumClassGenerator = new EnumClassGenerator($config, $printer);
    }

    public function getSchemaGenerator(Schema $schema): ?SchemaGeneratorInterface
    {
        if ($schema->isEnumOfStrings() || $schema->isEnumOfIntegers()) {
            return $this->enumGenerator;
        }

        if ($schema->isEnumOfScalarValues()) {
            return $this->enumClassGenerator;
        }

        if ($schema->type->contains(SchemaType::OBJECT)) {
            return $this->classGenerator;
        }

        return null;
    }
}
