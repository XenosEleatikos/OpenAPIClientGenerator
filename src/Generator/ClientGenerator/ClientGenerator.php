<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ClientGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Http\Client\ClientInterface;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApiClientGenerator\Generator\AbstractGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function array_merge;

readonly class ClientGenerator extends AbstractGenerator
{
    private ClassCommentGenerator $classCommentGenerator;

    public function __construct(
        Config $config,
        Printer $printer,
    ) {
        parent::__construct($config, $printer);
        $this->classCommentGenerator = new ClassCommentGenerator();
    }

    public function generate(OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace);
        $class = new ClassType('Client');
        $this->addConstructor($class);
        $class->setComment($this->classCommentGenerator->generateClassComments($openAPI));

        $namespace->add($class);

        $apiGenerator = new ApiGenerator($this->config, $this->printer);

        /** @var (Tag|string)[] $tags */
        $tags = array_merge(
            (array)$openAPI->tags,
            $openAPI->findUndeclaredTags()
        );
        foreach ($tags as $tag) {
            $tagName = $tag instanceof Tag ? $tag->name : $tag;
            $comment = $tag instanceof Tag ? $tag->description : null;
            $apiGenerator->generate($openAPI, $tag, $comment);

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
