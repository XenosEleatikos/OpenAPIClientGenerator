<?php

namespace Xenos\OpenApiClientGenerator\Generator\SchemaGenerator;

interface ContainerAwareInterface
{
    public function setContainer(SchemaGeneratorContainer $schemaGeneratorContainer): void;
}
