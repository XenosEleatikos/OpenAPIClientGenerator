<?php

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\TypeHintGenerator;
use stdClass;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\Schema;
use Xenos\OpenApi\Model\SchemaType;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\Response;

use function implode;
use function ucfirst;

readonly class ResponseGenerator extends AbstractGenerator
{
    private TypeHintGenerator $typeHintGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->typeHintGenerator = new TypeHintGenerator($config, $printer);
    }

    public function generate(OpenAPI $openAPI): void
    {
        foreach ($openAPI->components->responses as $name => $response) {
            $this->generateResponse($name, $response, $openAPI);
        }

        foreach ($this->findAnonymousResponses($openAPI) as $name => $response) {
            $this->generateResponse($name, $response, $openAPI);
        }
    }

    /** @return array<string, Response> */
    private function findAnonymousResponses(OpenAPI $openAPI): array
    {
        foreach ($openAPI->paths as $path) {
            foreach ($path->getAllOperations() as $operation) {
                foreach ($operation->responses as $statusCode => $response) {
                    if ($response instanceof Response) {
                        $anonymousResponses[self::createResponseClassNameFromOperationAndStatusCode($operation, (string)$statusCode)] = $response;
                    }
                }
            }
        }

        return $anonymousResponses ?? [];
    }

    public static function createResponseClassNameFromOperationAndStatusCode(Operation $operation, string $statusCode): string
    {
        return ucfirst($operation->operationId . $statusCode . 'Response');
    }

    public static function createResponseClassNameFromReferencePath(string $referencePath): string
    {
        $referencePath = explode('/', $referencePath);

        return array_pop($referencePath);
    }

    public function generateResponse(string $name, Response $response, OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Response');
        $class = new ClassType($name);
        $class->addComment($response->description);
        $this->addConstructor($class, $response, $openAPI, $name);
        $this->addFactory($class, $response, $openAPI);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Response/' . ucfirst($name) . '.php', $file);
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
            $className = self::createResponseClassNameFromReferencePath($jsonMediaType->schema->ref);
            /** @var Schema $schema */
            $schema = $openAPI->resolveReference($jsonMediaType->schema);
        } else {
            $schema = $jsonMediaType->schema;
            $className = null;
        }

        // @todo Optimize code
        if ($schema->type[0] === SchemaType::OBJECT) { // @phpstan-ignore-line
            $factory->addBody('    \\' . $this->config->namespace . '\Schema\\' . $className . '::make($data)');
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
