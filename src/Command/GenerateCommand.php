<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Command;

use Nette\PhpGenerator\PsrPrinter;
use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Generator\Generator;
use OpenApiClientGenerator\Printer\Printer;
use OpenApiClientGenerator\Model\OpenApi\OpenAPI;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function file_get_contents;
use function is_string;
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

        $this->addOption('specification', 's', InputOption::VALUE_REQUIRED, 'Path to OpenAPI specification');
        $this->addOption('root-namespace', 'r', InputOption::VALUE_REQUIRED, 'Root namespace for API client');
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output directory for the generated API client');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $specificationPath = $input->getOption('specification');
        if (!is_string($specificationPath) || empty($specificationPath)) {
            throw new RuntimeException('Parameter "--specification" ("-s") must be a path to an OpenAPI specification');
        }

        if ($this->isAbsolutePath($specificationPath)) {
            $absoluteFilePath = realpath($specificationPath);
        } else {
            $currentWorkingDirectory = getcwd();
            if ($currentWorkingDirectory === false) {
                $output->writeln('Could not determine the current working directory.');

                return Command::FAILURE;
            }

            $absoluteFilePath = realpath($currentWorkingDirectory . DIRECTORY_SEPARATOR . $specificationPath);
        }

        if (!is_string($absoluteFilePath)) {
            throw new RuntimeException('Cannot read file "' . $specificationPath . '"');
        }

        $specificationJson = file_get_contents($absoluteFilePath);
        if (!is_string($specificationJson)) {
            throw new RuntimeException('Cannot read file "' . $absoluteFilePath . '"');
        }

        $specification = json_decode($specificationJson);
        if (!$specification instanceof stdClass) {
            throw new RuntimeException('Invalid OpenApi specification given');
        }

        $specification = OpenAPI::make($specification);

        $rootNamespace = $input->getOption('root-namespace');

        if (!is_string($rootNamespace)) {
            $output->writeln('Invalid root namespace given');

            return Command::FAILURE;
        }

        $outputPath = $input->getOption('output');
        if (!is_string($outputPath) || empty($outputPath)) {
            throw new RuntimeException('Parameter "--output" ("-o") must be given');
        }

        if ($this->isAbsolutePath($outputPath)) {
            $absolutOutputPath = $outputPath;
        } else {
            $currentWorkingDirectory = getcwd();
            if ($currentWorkingDirectory === false) {
                $output->writeln('Could not determine the current working directory.');

                return Command::FAILURE;
            }

            $absolutOutputPath = $outputPath;
        }

        $config = new Config(
            namespace: $rootNamespace,
            directory: $absolutOutputPath
        );
        $clientGenerator = new Generator(
            $config,
            new Printer(
                $config,
                new PsrPrinter()
            )
        );
        $clientGenerator->generate($specification);

        return 0;
    }

    private function isAbsolutePath(string $path): bool
    {
        return $path[0] === '/';
    }
}
