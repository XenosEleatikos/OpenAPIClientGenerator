<?php

namespace Xenos\OpenApiClientGenerator\Generator\Printer;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

use function dirname;
use function file_put_contents;
use function is_dir;
use function mkdir;

readonly class Printer
{
    public function __construct(
        private PsrPrinter $printer,
    ) {
    }

    public function printFile(string $path, PhpFile $file): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname(path: $path), recursive: true);
        }

        file_put_contents(
            $path,
            $this->printer->printFile($file)
        );
    }
}
