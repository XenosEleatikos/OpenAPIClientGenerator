<?php

namespace OpenApiClientGenerator\Generator;

use GuzzleHttp\Psr7\Request;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\MediaType;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use OpenApiClientGenerator\Model\OpenApi\Operation;
use OpenApiClientGenerator\Model\OpenApi\ParameterLocation;
use OpenApiClientGenerator\Model\OpenApi\Paths;
use OpenApiClientGenerator\Model\OpenApi\Reference;
use OpenApiClientGenerator\Model\OpenApi\Tag;
use Psr\Http\Client\ClientInterface;

use function array_filter;
use function array_unique;
use function implode;
use function in_array;
use function str_replace;
use function ucfirst;

readonly class ApiGenerator extends AbstractGenerator
{
    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
    }

    public function generate(OpenAPI $openAPI, Tag $tag): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Api');
        $class = new ClassType($this->getClassName($tag));
        $this->addConstructor($class);
        $this->addClassComments($tag, $class);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        foreach (self::getAllOperations($openAPI->paths, $tag->name) as $operation) {
            $this->addMethodToApi($class, $openAPI, ...$operation);
        }

        $this->printer->printFile('src/Api/' . ucfirst($tag->name) . 'Api.php', $file);
    }

    /** @return array<int, array{0: string, 1: string, 2: Operation}> */
    private static function getAllOperations(Paths $paths, string $tagName): array
    {
        foreach ($paths as $path => $pathItem) {
            if (in_array($tagName, $pathItem->get?->tags ?? [])) {
                $operations[] = ['GET', $path, $pathItem->get];
            }
            if (in_array($tagName, $pathItem->put?->tags ?? [])) {
                $operations[] = ['PUT', $path, $pathItem->put];
            }
            if (in_array($tagName, $pathItem->post?->tags ?? [])) {
                $operations[] = ['POST', $path, $pathItem->post];
            }
            if (in_array($tagName, $pathItem->delete?->tags ?? [])) {
                $operations[] = ['DELETE', $path, $pathItem->delete];
            }
            if (in_array($tagName, $pathItem->options?->tags ?? [])) {
                $operations[] = ['OPTIONS', $path, $pathItem->options];
            }
            if (in_array($tagName, $pathItem->head?->tags ?? [])) {
                $operations[] = ['HEAD', $path, $pathItem->head];
            }
            if (in_array($tagName, $pathItem->patch?->tags ?? [])) {
                $operations[] = ['PATCH', $path, $pathItem->patch];
            }
            if (in_array($tagName, $pathItem->trace?->tags ?? [])) {
                $operations[] = ['TRACE', $path, $pathItem->trace];
            }
        }

        return $operations ?? [];
    }

    public function addClassComments(Tag $tag, ClassType $class): void
    {
        $comments[] = '# ' . $tag->name;
        $comments[] = $tag->description;
        if (isset($tag->externalDocs)) {
            $comments[] = '@link ' . $tag->externalDocs->url;
        }

        $class
            ->setComment(implode(PHP_EOL . PHP_EOL, array_filter($comments)));
    }

    public function addConstructor(ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('httpClient')
            ->setType(ClientInterface::class);
        $constructor->addPromotedParameter('config')
            ->setType($this->config->namespace . '\Config\Config');
    }

    public function addMethodToApi(
        ClassType $class,
        OpenAPI $openAPI,
        string $method,
        string $path,
        Operation $operation
    ): void {
        $pathParameters = $operation->parameters->getParametersByLocation(ParameterLocation::PATH);

        $apiMethod = $class->addMethod($operation->operationId);

        $returnTypes = [];
        $returnCodeSnippets = [];
        foreach ($operation->responses as $statusCode => $response) {
            if ($response instanceof Reference) {
                $returnTypes[$statusCode] = $this->config->namespace . '\Response\\' . ResponseGenerator::createResponseClassNameFromReferencePath($response->ref);
                $response = $openAPI->resolveReference($response);
            } else {
                $returnTypes[$statusCode] = $this->config->namespace . '\Response\\' . ResponseGenerator::createResponseClassNameFromOperationAndStatusCode($operation, $statusCode);
            }

            /** @var null|MediaType $jsonMediaType */
            $jsonMediaType = $response->content['application/json'] ?? null;
            if (isset($jsonMediaType)) {
                $returnCodeSnippets[] = '    ' . $statusCode . ' => \\' . $returnTypes[$statusCode] . '::make(json_decode($result->getBody()->getContents())),';
            } else {
                $returnCodeSnippets[] = '    ' . $statusCode . ' => \\' . $returnTypes[$statusCode] . '::make(),';
            }
        }

        $returnTypes = implode('|', array_unique($returnTypes));

        $apiMethod->setReturnType($returnTypes);

        $path = empty($pathParameters)
            ? '\'' . $path . '\'' // We use single quotes for strings without variables
            : '"' . $path . '"'; // We need double quotes, if the path contains variables

        foreach ($pathParameters as $pathParameter) {
            $path = str_replace('{' . $pathParameter->name . '}', '$' . $pathParameter->name, $path);
            $apiMethod->addParameter($pathParameter->name)
                ->setType('string');
        }

        $apiCall = '$result = $this->httpClient->sendRequest(' . PHP_EOL
            . '    new \\' . Request::class . '(' . PHP_EOL
            . '        method: \'' . $method . '\',' . PHP_EOL
            . '        uri: ' . $path . PHP_EOL
            . '    )' . PHP_EOL
            . ');';

        $apiMethod
            ->addBody($apiCall);
        $apiMethod->addBody('');
        $apiMethod->addBody('return match ($result->getStatusCode()) {');
        foreach ($returnCodeSnippets as $codeSnippet) {
            $apiMethod->addBody($codeSnippet);
        }
        $apiMethod->addBody('};');

        $comments = array_filter([
            $operation->summary,
            $operation->description,
        ]);
        $apiMethod->setComment(implode(PHP_EOL . PHP_EOL, $comments));
    }

    public static function getClassName(Tag $tag): string
    {
        return ucfirst($tag->name) . 'Api';
    }
}
