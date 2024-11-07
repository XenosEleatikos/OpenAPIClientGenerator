<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ResponseGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use stdClass;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\TypeHintGenerator;
use Xenos\OpenApiClientGenerator\Model\FullyQualifiedClassName;

use function implode;
use function ucfirst;

use const DIRECTORY_SEPARATOR;

readonly class ResponseGenerator
{
    public function __construct(
        private Config $config,
        private Printer $printer,
        private TypeHintGenerator $typeHintGenerator,
        private ResponseClassNameGenerator $responseClassNameGenerator,
    ) {
    }

    public function generate(OpenAPI $openAPI): void
    {
        foreach ($openAPI->components->responses as $name => $response) {
            $this->generateResponse(
                fqcn: $this->responseClassNameGenerator->createResponseClassNameFromComponentsKey($name),
                response: $response,
                openAPI: $openAPI
            );
        }

        foreach ($this->findAnonymousResponses($openAPI) as $fqcn => $response) {
            $this->generateResponse(
                fqcn: new FullyQualifiedClassName($fqcn),
                response: $response,
                openAPI: $openAPI
            );
        }
    }

    /** @return array<string, Response> */
    private function findAnonymousResponses(OpenAPI $openAPI): array
    {
        foreach ($openAPI->paths as $endpoint => $pathItem) {
            foreach ($pathItem->getAllOperations() as $method => $operation) {
                foreach ($operation->responses as $statusCode => $response) {
                    if ($response instanceof Response) {
                        $anonymousResponses[(string)$this->responseClassNameGenerator->createResponseClassName($method, $endpoint, $operation, (string)$statusCode)] = $response;
                    }
                }
            }
        }

        return $anonymousResponses ?? [];
    }

    private function generateResponse(FullyQualifiedClassName $fqcn, Response $response, OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($fqcn->getNamespace());
        $class = new ClassType($fqcn->getClassName());
        $class->addComment($response->description);
        $this->addConstructor($class, $response, $openAPI, (string)$fqcn);
        $this->addFactory($class, $response, $openAPI);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile(
            path: $this->config->directory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Response' . DIRECTORY_SEPARATOR . ucfirst($fqcn->getClassName()) . '.php',
            file: $file
        );
    }

    private function addFactory(
        ClassType $class,
        Response $response,
        OpenAPI $openAPI,
    ): void {
        $factory = $class->addMethod('make')
            ->setStatic()
            ->setReturnType('self');

        /** @var null|MediaType $jsonMediaType */
        $jsonMediaType = $response->content['application/json'] ?? null;

        $factory->addParameter('statusCode')
            ->setType('string');

        if (isset($jsonMediaType)) {
            $factory->addParameter('data')
                ->setType(stdClass::class);
        } else {
            $factory
                ->addBody('return new self($statusCode);');

            return;
        }

        $factory
            ->addBody('return new self(' . PHP_EOL . '    $statusCode, ');

        if ($jsonMediaType->schema instanceof Reference) {
            $fqcn = $this->responseClassNameGenerator->createResponseClassNameFromReferencePath($jsonMediaType->schema->ref);
            /** @var Schema $schema */
            $schema = $openAPI->resolveReference($jsonMediaType->schema);
        } else {
            $schema = $jsonMediaType->schema;
            $fqcn = null;
        }

        // @todo Optimize code
        if ($schema->type[0] === SchemaType::OBJECT) { // @phpstan-ignore-line
            $factory->addBody('    \\' . $this->config->namespace . '\Schema\\' . $fqcn . '::make($data)');
        } else {
            $factory->addBody('    $data');
        }

        $factory->addBody(');');
    }

    private function addConstructor(ClassType $class, Response $response, OpenAPI $openAPI, string $name): void
    {
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
                implode(
                    '|',
                    $this->typeHintGenerator->getReturnTypes(
                        $mediaType->schema,
                        $openAPI,
                        $name,
                        'jsonSchema'
                    )
                )
            );

    }
}
