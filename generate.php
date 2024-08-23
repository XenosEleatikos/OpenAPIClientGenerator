<?php

use PHPModelGenerator\SchemaProvider\RecursiveDirectoryProvider;

include 'vendor/autoload.php';

$generator = new \PHPModelGenerator\ModelGenerator(
    (new \PHPModelGenerator\Model\GeneratorConfiguration())
        ->setNamespacePrefix('MyApp\Model')
        ->setImmutable(false)
);

$generator
    ->generateModels(new RecursiveDirectoryProvider(__DIR__ . '/OpenAPI-Specification/schemas/v3.1'), __DIR__ . '/src/OpenApi');
