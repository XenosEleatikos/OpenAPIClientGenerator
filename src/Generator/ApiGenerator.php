<?php

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\ParameterLocation;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Tag;

use function array_filter;
use function array_unique;
use function implode;
use function in_array;
use function str_replace;
use function ucfirst;

readonly class ApiGenerator extends AbstractGenerator
{
    private MethodNameGenerator $methodNameGenerator;

    public function __construct(Config $config, Printer $printer)
    {
        parent::__construct($config, $printer);
        $this->methodNameGenerator = new MethodNameGenerator();
    }

    public function generate(OpenAPI $openAPI, string|Tag $tag, ?string $comment): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Api');
        $tagName = $this->getTagName($tag);

        $class = new ClassType($this->getClassName($tagName));
        $this->addConstructor($class);
        $this->addClassComments($tagName, $comment, $class);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        foreach (self::getAllOperations($openAPI->paths, $tagName) as $operation) {
            $this->addMethodToApi($class, $openAPI, ...$operation);
        }

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Api/' . ucfirst($tagName) . 'Api.php', $file);
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

        return $operations ?? []; // @phpstan-ignore-line
    }

    public function addClassComments(string|Tag $tag, ?string $comment, ClassType $class): void
    {
        $comments[] = '# ' . self::getTagName($tag);
        $comments[] = $comment;
        if ($tag instanceof Tag && isset($tag->externalDocs)) {
            $comments[] = '@link ' . $tag->externalDocs->url;
        }

        $class
            ->setComment(implode(PHP_EOL . PHP_EOL, array_filter($comments)));
    }

    public function addConstructor(ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('httpClient')
            ->setType('\Psr\Http\Client\ClientInterface');
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

        $apiMethod = $class->addMethod($this->methodNameGenerator->generateMethodName($method, $path, $operation));

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
                $returnCodeSnippets[] = '    ' . $statusCode . ' => \\' . $returnTypes[$statusCode] . '::make(' . self::generateStatusCode($statusCode) . ', json_decode($result->getBody()->getContents())),';
            } else {
                $returnCodeSnippets[] = '    ' . $statusCode . ' => \\' . $returnTypes[$statusCode] . '::make(' . self::generateStatusCode($statusCode) . '),';
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
            . '    new  \GuzzleHttp\Psr7\Request(' . PHP_EOL
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

    private static function generateStatusCode(string $statusCode): string
    {
        return '\'' . $statusCode . '\'';
    }

    public static function getClassName(string $tagName): string
    {
        return ucfirst($tagName) . 'Api';
    }

    private static function getTagName(string|Tag $tag): string
    {
        return $tag instanceof Tag ? $tag->name : $tag;
    }
}
