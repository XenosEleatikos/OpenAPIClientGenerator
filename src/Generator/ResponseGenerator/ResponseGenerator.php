<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ResponseGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaGeneratorContainer;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function implode;
use function ucfirst;

use const DIRECTORY_SEPARATOR;

readonly class ResponseGenerator
{
    public function __construct(
        private Config $config,
        private Printer $printer,
        private SchemaGeneratorContainer $schemaGeneratorContainer,
    ) {
    }

    /** @param array<string, Response> $responses */
    public function generate(array $responses, OpenAPI $openAPI): void
    {
        foreach ($responses as $fqcn => $response) {
            $this->generateResponse(new FullyQualifiedClassName($fqcn), $response, $openAPI);
        }
    }

    private function generateResponse(FullyQualifiedClassName $fqcn, Response $response, OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($fqcn->getNamespace());
        $class = new ClassType($fqcn->getClassName());
        $class->addComment($response->description);
        $this->addConstructor(
            class: $class,
            response: $response,
            openAPI: $openAPI,
            fullyQualifiedClassName: $fqcn
        );
        $this->addFactory($class, $response, $openAPI, $fqcn);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile(
            path: $this->config->directory
            . DIRECTORY_SEPARATOR
            . 'src'
            . DIRECTORY_SEPARATOR
            . 'Response'
            . DIRECTORY_SEPARATOR
            . ucfirst($fqcn->getClassName())
            . '.php',
            file: $file
        );
    }

    private function addFactory(
        ClassType $class,
        Response $response,
        OpenAPI $openAPI,
        FullyQualifiedClassName $fqcn
    ): void {
        $factory = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('self');

        /** @var null|MediaType $jsonMediaType */
        $jsonMediaType = $response->content['application/json'] ?? null;
        $jsonMediaTypeSchemaOrReference = $jsonMediaType?->schema;

        $factory->addParameter('statusCode')
            ->setType('string');

        $rawTypes = $this->schemaGeneratorContainer->getRawDataTypes(
            schemaOrReference: $jsonMediaTypeSchemaOrReference,
            openAPI: $openAPI,
        );

        if (isset($jsonMediaType)) {
            $factory->addParameter('data')
                ->setType(implode(separator: '|', array: $rawTypes));
        } else {
            /** @todo Implement other media types */
            $factory
                ->addBody('return new self($statusCode);');

            return;
        }

        $factory
            ->addBody('return new self(' . PHP_EOL . '    $statusCode, ');

        $factoryCall = $this->schemaGeneratorContainer->getFactoryCall(
            schemaOrReference: $jsonMediaTypeSchemaOrReference,
            openAPI: $openAPI,
            parentClassName: $fqcn->getClassName(),
            propertyName: 'jsonSchema',
            parameter: '$data',
        );
        $factory->addBody(
            code: '    ' . $factoryCall
        );

        $factory->addBody(');');
    }

    private function addConstructor(
        ClassType $class,
        Response $response,
        OpenAPI $openAPI,
        FullyQualifiedClassName $fullyQualifiedClassName
    ): void {
        $constructor = $class->addMethod('__construct');

        $constructor
            ->addPromotedParameter('statusCode')
            ->setType('string');

        if (!isset($response->content)) {
            return;
        }

        /** @todo Implement other media types */
        /** @var MediaType $mediaType */
        $mediaType = $response->content['application/json'];

        $constructor
            ->addPromotedParameter('content')
            ->setType(
                type: implode(
                    separator: '|',
                    array: $this->schemaGeneratorContainer->getReturnTypes(
                        schemaOrReference: $mediaType->schema,
                        openAPI: $openAPI,
                        parentClassName: $fullyQualifiedClassName->getClassName(),
                        propertyName: 'jsonSchema'
                    )
                )
            );
    }
}
