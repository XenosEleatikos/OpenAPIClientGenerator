<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ApiGenerator;

use Xenos\OpenApi\Model\Tag;

use function array_filter;
use function implode;

class ClassCommentGenerator
{
    public function generateClassComment(string|Tag $tag): string
    {
        $comments[] = '# ' . ($tag instanceof Tag ? $tag->name : $tag);
        $comments[] = $tag instanceof Tag ? $tag->description : null;
        if ($tag instanceof Tag && isset($tag->externalDocs)) {
            $link = '@link ' . $tag->externalDocs->url;
            if (!empty($tag->externalDocs->description)) {
                $link .= ' ' . $tag->externalDocs->description;
            }
            $comments[] = $link;
        }

        return implode(PHP_EOL . PHP_EOL, array_filter($comments));
    }
}
