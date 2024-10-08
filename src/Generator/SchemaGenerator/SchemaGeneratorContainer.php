<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

use Xenos\OpenApi\Model\Schema;

class SchemaGeneratorContainer
{
    /** @var SchemaGeneratorInterface[] */
    private array $generators = [];

    public function add(SchemaGeneratorInterface... $generators): self
    {
        foreach ($generators as $generator) {
            if ($generator instanceof ContainerAwareInterface) {
                $generator->setContainer($this);
            }
        }

        $this->generators = $generators;

        return $this;
    }

    public function getSchemaGenerator(Schema $schema): ?SchemaGeneratorInterface
    {
        foreach ($this->generators as $generator) {
            if ($generator->isResponsible($schema)) {
                return $generator;
            }
        }

        return null;
    }
}
