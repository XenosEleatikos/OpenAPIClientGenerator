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
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\ClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumClassGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\EnumGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\TypeHintGenerator;

class GeneratorFactory
{
    public static function make(Config $config): Generator
    {
        $printer = new Printer(new PsrPrinter());
        $schemaClassNameGenerator = new SchemaClassNameGenerator();
        $typeHintGenerator = new TypeHintGenerator(
            config: $config,
            schemaClassNameGenerator: $schemaClassNameGenerator
        );

        return new Generator(
            schemaGenerator: new SchemaGenerator(
                schemaClassNameGenerator: $schemaClassNameGenerator,
                schemaGeneratorContainer: (new SchemaGeneratorContainer())
                    ->add(
                        new EnumGenerator($config, $printer),
                        new EnumClassGenerator($config, $printer),
                        new ClassGenerator(
                            config: $config,
                            printer: $printer,
                            typeHintGenerator: $typeHintGenerator,
                            schemaClassNameGenerator: $schemaClassNameGenerator,
                        ),
                    ),
            ),
            responseGenerator: new ResponseGenerator(
                config: $config,
                printer: $printer,
                typeHintGenerator: $typeHintGenerator,
            ),
            clientGenerator: new ClientGenerator(
                config: $config,
                printer: $printer,
                classCommentGenerator: new ClassCommentGenerator(),
                apiGenerator: new ApiGenerator(
                    config: $config,
                    printer: $printer,
                    methodNameGenerator: new MethodNameGenerator(),
                    classCommentGenerator: new \Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ClassCommentGenerator(),
                    methodCommentGenerator: new MethodCommentGenerator()
                )
            ),
            configGenerator: new ConfigGenerator($config, $printer),
        );
    }
}
