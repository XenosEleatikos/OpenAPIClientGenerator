<?php

namespace OpenApiClientGenerator\Generator;

use OpenApiClientGenerator\Config\Config;
use OpenApiClientGenerator\Printer\Printer;

abstract readonly class AbstractGenerator
{
    public function __construct(
        protected Config $config,
        protected Printer $printer,
    ) {
    }
}
