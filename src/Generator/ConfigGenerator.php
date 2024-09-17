<?php

namespace Xenos\OpenApiClientGenerator\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Xenos\OpenApi\Model\OpenAPI;

readonly class ConfigGenerator extends AbstractGenerator
{
    public function generate(OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Config');
        $class = new ClassType('Config');
        $this->addConstructorToConfig($class);

        $namespace->add($class);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Config/Config.php', $file);

        $this->generateServerEnum($openAPI);
    }

    private function generateServerEnum(OpenAPI $openAPI): void
    {
        $namespace = new PhpNamespace($this->config->namespace . '\Config');
        $enum = new EnumType('Server');
        foreach ($openAPI->servers as $key => $server) {
            $case = $enum->addCase('SERVER_'.$key, $server->url);
            if (!empty($server->description)) {
                $case->addComment($server->description);
            }
        }
        $namespace->add($enum);

        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addNamespace($namespace);

        $this->printer->printFile($this->config->directory . DIRECTORY_SEPARATOR . 'src/Config/Server.php', $file);

    }

    public function addConstructorToConfig(ClassType $class): void
    {
        $constructor = $class->addMethod('__construct');
        $constructor
            ->addPromotedParameter('server')
            ->setType($this->config->namespace . '\Config\Server');
    }
}
