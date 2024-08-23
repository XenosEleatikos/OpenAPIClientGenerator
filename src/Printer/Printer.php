<?php

namespace OpenApiClientGenerator\Printer;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use OpenApiClientGenerator\Config\Config;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;

readonly class Printer
{
    public function __construct(
        private Config $config,
        private PsrPrinter $printer,
    ) {
    }

    public function printFile(string $path, PhpFile $file): void
    {
        $fullPath = $this->config->directory . DIRECTORY_SEPARATOR . $path;

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname(path: $fullPath), recursive: true);
        }

        file_put_contents(
            $fullPath,
            $this->printer->printFile($file)
        );
    }
}
