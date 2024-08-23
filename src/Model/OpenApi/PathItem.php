<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class PathItem implements JsonSerializable
{
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public Servers $servers = new Servers(),
        public ?Operation $get = null,
        public ?Operation $put = null,
        public ?Operation $post = null,
        public ?Operation $delete = null,
        public ?Operation $options = null,
        public ?Operation $head = null,
        public ?Operation $patch = null,
        public ?Operation $trace = null,
    ) {
    }

    public static function make(stdClass $schema): self
    {
        return new self(
            summary: $schema->summary ?? null,
            description: $schema->description ?? null,
            servers: isset($schema->servers) ? Servers::make($schema->servers) : new Servers(),
            get: isset($schema->get) ? Operation::make($schema->get) : null,
            put: isset($schema->put) ? Operation::make($schema->put) : null,
            post: isset($schema->post) ? Operation::make($schema->post) : null,
            delete: isset($schema->delete) ? Operation::make($schema->delete) : null,
            options: isset($schema->options) ? Operation::make($schema->options) : null,
            head: isset($schema->head) ? Operation::make($schema->head) : null,
            patch: isset($schema->patch) ? Operation::make($schema->patch) : null,
            trace: isset($schema->trace) ? Operation::make($schema->trace) : null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'summary' => $this->summary,
            'description' => $this->description,
            'servers' => $this->servers->count() === 0 ? null : $this->servers->jsonSerialize(),
            'get' => $this->get?->jsonSerialize(),
            'put' => $this->put?->jsonSerialize(),
            'post' => $this->post?->jsonSerialize(),
            'delete' => $this->delete?->jsonSerialize(),
            'options' => $this->options?->jsonSerialize(),
            'head' => $this->head?->jsonSerialize(),
            'patch' => $this->patch?->jsonSerialize(),
            'trace' => $this->trace?->jsonSerialize(),
        ]);
    }

    /** @return Operation[] */
    public function getAllOperations(): array
    {
        return array_filter([
                $this->get,
                $this->put,
                $this->post,
                $this->delete,
                $this->options,
                $this->head,
                $this->patch,
                $this->trace,
        ]);
    }
}
