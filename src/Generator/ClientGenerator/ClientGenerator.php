<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ClientGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Http\Client\ClientInterface;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ApiGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function array_merge;

readonly class ClientGenerator
{
    public function __construct(
        private Config $config,
        private Printer $printer,
        private ClassCommentGenerator $classCommentGenerator,
        private ApiGenerator $apiGenerator,
    ) {
    }

    public function generate(OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace);
        $class = new ClassType('Client');
        $this->addConstructor($class);
        $class->setComment($this->classCommentGenerator->generateClassComments($openAPI));

        $namespace->add($class);

        foreach ($openAPI->findUsedTags() as $tagName) {
            $this->apiGenerator->generate($openAPI, $tagName);

            $classname = 'Api\\' . ApiGenerator::getClassName($tagName);

            $method = $class->addMethod($tagName);
            $method->addBody('static $' . $tagName . 'Api = null;');
            $method->addBody(
                'return $' . $tagName . 'Api ??= new ' . $classname . '($this->httpClient, $this->config);'
            );
            $method->setReturnType($this->config->namespace . '\\' . $classname);
        }

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Client.php', $file);
    }

    private function addConstructor(ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor
            ->addPromotedParameter('httpClient')
            ->setPrivate()
            ->setType(ClientInterface::class);
        $constructor
            ->addPromotedParameter('config')
            ->setPrivate()
            ->setType($this->config->namespace . '\Config\Config');
    }
}
