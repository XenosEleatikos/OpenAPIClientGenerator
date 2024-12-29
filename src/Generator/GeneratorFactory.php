<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\PsrPrinter;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ApiGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator\ClassCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator\ClientGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseFinder;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\ClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaFinder;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;

class GeneratorFactory
{
    public static function make(Config $config): Generator
    {
        $printer = new Printer(new PsrPrinter());
        $schemaClassNameGenerator = new SchemaClassNameGenerator();
        $methodNameGenerator = new MethodNameGenerator();
        $classNameGenerator = new ResponseClassNameGenerator(
            config: $config,
            methodNameGenerator: $methodNameGenerator
        );
        $schemaGeneratorContainer = new SchemaGeneratorContainer(
            config: $config,
            schemaClassNameGenerator: $schemaClassNameGenerator,
            classGenerator: new ClassGenerator(
                config: $config,
                printer: $printer,
            ),
            enumGenerator: new EnumGenerator(
                config: $config,
                printer: $printer,
            ),
            enumClassGenerator: new EnumClassGenerator($config, $printer),
        );

        $responseFinder = new ResponseFinder($classNameGenerator);

        return new Generator(
            schemaGenerator: new SchemaGenerator(
                schemaGeneratorContainer: $schemaGeneratorContainer,
            ),
            responseGenerator: new ResponseGenerator(
                config: $config,
                printer: $printer,
                schemaGeneratorContainer: $schemaGeneratorContainer,
            ),
            clientGenerator: new ClientGenerator(
                config: $config,
                printer: $printer,
                classCommentGenerator: new ClassCommentGenerator(),
                apiGenerator: new ApiGenerator(
                    config: $config,
                    printer: $printer,
                    methodNameGenerator: $methodNameGenerator,
                    classCommentGenerator: new \Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ClassCommentGenerator(),
                    methodCommentGenerator: new MethodCommentGenerator(),
                    classNameGenerator: $classNameGenerator,
                )
            ),
            configGenerator: new ConfigGenerator($config, $printer),
            responseFinder: $responseFinder,
            schemaFinder: new SchemaFinder(
                schemaClassNameGenerator: $schemaClassNameGenerator,
                responseFinder: $responseFinder,
            )
        );
    }
}
