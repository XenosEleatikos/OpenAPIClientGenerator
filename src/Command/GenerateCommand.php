<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Command;

use Nette\PhpGenerator\PsrPrinter;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\Generator;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function file_get_contents;
use function json_decode;

#[AsCommand(
    name: 'generate',
    description: 'Generates an API client'
)]
class GenerateCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->addArgument('specification', InputArgument::REQUIRED, 'Path to OpenAPI specification');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $specification = $input->getArgument('specification');
        $specification = json_decode(file_get_contents($specification));
        $specification = OpenAPI::make($specification);

        $config = new Config(
            $specification,
            'PetstoreClient',
            __DIR__.'/../generated'
        );
        $clientGenerator = new Generator(
            $config,
            new Printer(
                $config,
                new PsrPrinter()
            )
        );
        $clientGenerator->generate();

        return 0;
    }
}
