<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ClientGenerator;

use Xenos\OpenApi\Model\OpenAPI;

use function array_filter;
use function implode;

class ClassCommentGenerator
{
    public function generateClassComments(OpenAPI $openAPI): string
    {
        $comments[] = '# ' . $openAPI->info->title;
        $comments[] = 'Version: ' . $openAPI->info->version;
        $comments[] = $openAPI->info->summary;
        $comments[] = $openAPI->info->description;
        if ($openAPI->info->hasContactInformation()) {
            $comments[] = '## Contact';
            if (!empty($openAPI->info->contact->name) && !empty($openAPI->info->contact->url)) {
                $comments[] = '[' . $openAPI->info->contact->name . ']' . '(' . $openAPI->info->contact->url . ')';
            } else {
                $comments[] = $openAPI->info->contact?->name;
                $comments[] = $openAPI->info->contact?->url;
            }
            if (isset($openAPI->info->contact?->email)) {
                $comments[] = 'E-mail: [' . $openAPI->info->contact->email . '](' . $openAPI->info->contact->email . ')';
            }
        }
        if ($openAPI->externalDocs !== null) {
            $comments[] = '## Documentation';
            $comments[] = $openAPI->externalDocs->description;
            $comments[] = $openAPI->externalDocs->url;
        }
        if (!empty($openAPI->info->termsOfService)) {
            $comments[] = '## Terms of service';
            $comments[] = $openAPI->info->termsOfService;
        }
        if (!empty($openAPI->info->license)) {
            $comments[] = '## License';
            if (!empty($openAPI->info->license->url)) {
                $comments[] = '[' . $openAPI->info->license->name . ']' . '(' . $openAPI->info->license->url . ')';
            } elseif (!empty($openAPI->info->license->identifier)) {
                $comments[] = '[' . $openAPI->info->license->name . ']' . '(' . 'https://opensource.org/licenses/' . $openAPI->info->license->identifier . ')';
            } else {
                $comments[] = $openAPI->info->license->name;
            }
        }

        return implode(PHP_EOL . PHP_EOL, array_filter($comments));
    }
}
