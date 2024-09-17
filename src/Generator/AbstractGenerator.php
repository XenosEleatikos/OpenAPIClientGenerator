<?php

namespace Xenos\OpenApiClientGenerator\Generator;

use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

abstract readonly class AbstractGenerator
{
    public function __construct(
        protected Config $config,
        protected Printer $printer,
    ) {
    }
}
