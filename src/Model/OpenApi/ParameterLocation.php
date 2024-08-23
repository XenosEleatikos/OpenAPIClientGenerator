<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;

enum ParameterLocation: string implements JsonSerializable
{
    case QUERY = 'query';
    case HEADER = 'header';
    case PATH = 'path';
    case COOKIE = 'cookie';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
