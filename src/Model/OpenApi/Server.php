<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Server implements JsonSerializable
{
    public function __construct(
        public string $url,
        public ?string $description = null,
        public ServerVariables $variables = new ServerVariables(),
    ) {
    }

    public static function make(stdClass $server): self
    {
        return new self(
            url: $server->url,
            description: $server->description ?? null,
            variables: isset($server->variables) ? ServerVariables::make($server->variables) : new ServerVariables(),
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'url' => $this->url,
            'description' => $this->description,
            'variables' => $this->variables->count() === 0 ? null : $this->variables->jsonSerialize(),
        ]);
    }
}
