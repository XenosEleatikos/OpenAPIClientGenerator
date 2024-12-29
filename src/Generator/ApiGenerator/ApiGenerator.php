<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ApiGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApi\Model\MediaType;
use Xenos\OpenApi\Model\Method;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\ParameterLocation;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Reference;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;

use function array_unique;
use function implode;
use function str_replace;
use function ucfirst;

readonly class ApiGenerator
{
    public function __construct(
        private Config $config,
        private Printer $printer,
        private MethodNameGenerator $methodNameGenerator,
        private ClassCommentGenerator $classCommentGenerator,
        private MethodCommentGenerator $methodCommentGenerator,
        private ResponseClassNameGenerator $classNameGenerator,
    ) {
    }

    public function generate(OpenAPI $openAPI, string $tag): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Api');

        $class = new ClassType($this->getClassName($tag));
        $this->addConstructor($class);
        $class->setComment($this->classCommentGenerator->generateClassComment($openAPI->tags[$tag] ?? $tag));

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        foreach (self::getAllOperations($openAPI->paths, $tag) as $operation) {
            $this->addMethodToApi($class, $openAPI, ...$operation);
        }

        $this->printer->printFile($this->getApiPath() . DIRECTORY_SEPARATOR . self::getClassName($tag) . '.php', $file);
    }

    /** @return array<int, array{0: Method, 1: string, 2: Operation}> */
    private static function getAllOperations(Paths $paths, string $tag): array
    {
        foreach ($paths as $path => $pathItem) {
            if ($pathItem->get?->hasTag($tag)) {
                $operations[] = [Method::GET, $path, $pathItem->get];
            }
            if ($pathItem->put?->hasTag($tag)) {
                $operations[] = [Method::PUT, $path, $pathItem->put];
            }
            if ($pathItem->post?->hasTag($tag)) {
                $operations[] = [Method::POST, $path, $pathItem->post];
            }
            if ($pathItem->delete?->hasTag($tag)) {
                $operations[] = [Method::DELETE, $path, $pathItem->delete];
            }
            if ($pathItem->options?->hasTag($tag)) {
                $operations[] = [Method::OPTIONS, $path, $pathItem->options];
            }
            if ($pathItem->head?->hasTag($tag)) {
                $operations[] = [Method::HEAD, $path, $pathItem->head];
            }
            if ($pathItem->patch?->hasTag($tag)) {
                $operations[] = [Method::PATCH, $path, $pathItem->patch];
            }
            if ($pathItem->trace?->hasTag($tag)) {
                $operations[] = [Method::TRACE, $path, $pathItem->trace];
            }
        }

        return $operations ?? [];
    }

    private function addConstructor(ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('httpClient')
            ->setType('\Psr\Http\Client\ClientInterface');
        $constructor->addPromotedParameter('config')
            ->setType($this->config->namespace . '\Config\Config');
    }

    private function addMethodToApi(
        ClassType $class,
        OpenAPI $openAPI,
        Method $method,
        string $path,
        Operation $operation
    ): void {
        $apiMethod = $class->addMethod($this->methodNameGenerator->generateMethodName($method, $path, $operation));

        $returnTypes = [];
        $returnCodeSnippets = [];
        foreach ($operation->responses as $statusCode => $response) {
            $statusCode = (string)$statusCode;
            if ($response instanceof Reference) {
                $returnTypes[$statusCode] = $this->classNameGenerator->fromReferencePath($response->ref);
                $response = $openAPI->resolveReference($response);
            } else {
                $returnTypes[$statusCode] = $this->classNameGenerator->fromOperation($method, $path, $operation, $statusCode);
            }

            /** @var null|MediaType $jsonMediaType */
            $jsonMediaType = $response->content['application/json'] ?? null;
            if (isset($jsonMediaType)) {
                $returnCodeSnippets[] = '    ' . $statusCode . ' => \\' . $returnTypes[$statusCode] . '::make(statusCode: ' . self::generateStatusCode($statusCode) . ', data: \json_decode($result->getBody()->getContents())),';
            } else {
                $returnCodeSnippets[] = '    ' . $statusCode . ' => \\' . $returnTypes[$statusCode] . '::make(statusCode: ' . self::generateStatusCode($statusCode) . '),';
            }
        }

        $returnTypes = implode('|', array_unique($returnTypes));

        $apiMethod->setReturnType($returnTypes ?: 'void');

        $pathParameters = $operation->parameters->getParametersByLocation(ParameterLocation::PATH);
        $path = empty($pathParameters)
            ? '\'' . $path . '\'' // We use single quotes for strings without variables
            : '"' . $path . '"'; // We need double quotes, if the path contains variables

        foreach ($pathParameters as $pathParameter) {
            $path = str_replace('{' . $pathParameter->name . '}', '$' . $pathParameter->name, $path);
            $apiMethod->addParameter($pathParameter->name)
                ->setType('string');
        }

        $apiCall = !empty($returnCodeSnippets) ? '$result = ' : '';
        $apiCall .= $this->createApiCall($method, $path);

        $apiMethod
            ->addBody($apiCall);

        if (!empty($returnCodeSnippets)) {
            $apiMethod->addBody('');
            $apiMethod->addBody('return match ($result->getStatusCode()) {');
            foreach ($returnCodeSnippets as $codeSnippet) {
                $apiMethod->addBody($codeSnippet);
            }
            $apiMethod->addBody('};');
        }

        $apiMethod->setComment($this->methodCommentGenerator->generateMethodComment($operation));
    }

    private static function generateStatusCode(string $statusCode): string
    {
        return '\'' . $statusCode . '\'';
    }

    public static function getClassName(string $tagName): string
    {
        return ucfirst($tagName) . 'Api';
    }

    private function getApiPath(): string
    {
        return $this->config->directory . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Api';
    }

    public function createApiCall(Method $method, string $path): string
    {
        return '$this->httpClient->sendRequest(' . PHP_EOL
            . '    new \GuzzleHttp\Psr7\Request(' . PHP_EOL
            . '        method: \'' . $method->value . '\',' . PHP_EOL
            . '        uri: ' . $path . PHP_EOL
            . '    )' . PHP_EOL
            . ');';
    }
}
