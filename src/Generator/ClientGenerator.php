<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApi\Model\OpenAPI;

readonly class ClientGenerator extends AbstractGenerator
{
    public function generate(OpenAPI $openAPI): void
    {
        $configGenerator = new ConfigGenerator($this->config, $this->printer);
        $configGenerator->generate($openAPI);

        $namespace = new PhpNamespace($this->config->namespace);
        $class = new ClassType('Client');
        $this->addConstructor($class);
        $this->addClassComments($openAPI, $class);

        $namespace->add($class);

        $apiGenerator = new ApiGenerator($this->config, $this->printer);
        // @todo Implement API methods without tags
        foreach ($openAPI->tags as $tag) {
            $apiGenerator->generate($openAPI, $tag);

            $classname = 'Api\\' . ApiGenerator::getClassName($tag);

            $method = $class->addMethod($tag->name);
            $method->addBody('static $' . $tag->name . 'Api = null;');
            $method->addBody(
                'return $' . $tag->name . 'Api ??= new ' . $classname . '($this->httpClient, $this->config);'
            );
            $method->setReturnType($this->config->namespace . '\\' . $classname);
        }

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Client.php', $file);
    }

    public function addClassComments(OpenAPI $openAPI, ClassType $class): void
    {
        $comments[] = '# ' . $openAPI->info->title;
        $comments[] = 'Version: ' . $openAPI->info->version;
        $comments[] = $openAPI->info->summary;
        $comments[] = $openAPI->info->description;
        if (isset($openAPI->info->termsOfService)) {
            $comments[] = 'Terms of service: ' . $openAPI->info->termsOfService;
        }

        $class
            ->setComment(\implode(PHP_EOL . PHP_EOL, \array_filter($comments)));
    }

    public function addConstructor(ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor
            ->addPromotedParameter('httpClient')
            ->setPrivate()
            ->setType('Psr\Http\Client\ClientInterface');
        $constructor
            ->addPromotedParameter('config')
            ->setPrivate()
            ->setType($this->config->namespace . '\Config\Config');
    }
}
