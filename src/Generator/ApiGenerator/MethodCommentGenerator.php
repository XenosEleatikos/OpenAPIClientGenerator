<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ApiGenerator;

use Xenos\OpenApi\Model\Operation;

use function array_filter;
use function implode;

class MethodCommentGenerator
{
    public function generateMethodComment(Operation $operation): string
    {
        $comments = array_filter([
            $operation->summary,
            $operation->description,
        ]);

        return implode(PHP_EOL . PHP_EOL, $comments);
    }
}
