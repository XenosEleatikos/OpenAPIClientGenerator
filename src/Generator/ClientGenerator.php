<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Psr\Http\Client\ClientInterface;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Tag;

use function array_filter;
use function array_merge;
use function implode;

readonly class ClientGenerator extends AbstractGenerator
{
    public function generate(OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace);
        $class = new ClassType('Client');
        $this->addConstructor($class);
        $this->addClassComments($openAPI, $class);

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

    private function addClassComments(OpenAPI $openAPI, ClassType $class): void
    {
        $comments[] = '# ' . $openAPI->info->title;
        $comments[] = 'Version: ' . $openAPI->info->version;
        $comments[] = $openAPI->info->summary;
        $comments[] = $openAPI->info->description;
        if (isset($openAPI->info->termsOfService)) {
            $comments[] = 'Terms of service: ' . $openAPI->info->termsOfService;
        }

        $class
            ->setComment(implode(PHP_EOL . PHP_EOL, array_filter($comments)));
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
