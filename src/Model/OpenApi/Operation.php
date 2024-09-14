<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;
use function in_array;

class Operation implements JsonSerializable
{
    public function __construct(
        /** @var string[] $tags */
        public array                  $tags = [],
        public ?string                $summary = null,
        public ?string                $description = null,
        public ?ExternalDocumentation $externalDocs = null,
        public ?string                $operationId = null,
        public ParametersOrReferences $parameters = new ParametersOrReferences(),
        public ?RequestBody           $requestBody = null,
        public ResponsesOrReferences  $responses = new ResponsesOrReferences(),
        public PathItemsOrReferences  $callbacks = new PathItemsOrReferences(),
        public bool                   $deprecated = false,
        /** @var string[] $security */
        public array                  $security = [],
        public Servers                $servers = new Servers(),
    ) {
    }

    public static function make(stdClass $operation): self
    {
        return new self(
            tags: $operation->tags ?? [],
            summary: $operation->summary ?? null,
            description: $operation->description ?? null,
            externalDocs: $operation->externalDocs ?? null,
            operationId: $operation->operationId ?? null,
            parameters: isset($operation->parameters) ? ParametersOrReferences::make($operation->parameters) : new ParametersOrReferences(),
            requestBody: isset($operation->requestBody) ? RequestBody::make($operation->requestBody) : null,
            responses: isset($operation->responses) ? ResponsesOrReferences::make($operation->responses) : new ResponsesOrReferences(),
            callbacks: isset($operation->callbacks) ? PathItemsOrReferences::make($operation->callbacks) : new PathItemsOrReferences(),
            deprecated: $operation->deprecated ?? false,
            security: $operation->security ?? [],
            servers: isset($operation->servers) ? Servers::make($operation->servers) : new Servers(),
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'tags' => $this->tags,
            'summary' => $this->summary,
            'description' => $this->description,
            'externalDocs' => $this->externalDocs?->jsonSerialize(),
            'operationId' => $this->operationId,
            'parameters' => $this->parameters->count() === 0 ? null : $this->parameters->jsonSerialize(),
            'requestBody' => $this->requestBody?->jsonSerialize(),
            'responses' => $this->responses->count() === 0 ? null : $this->responses->jsonSerialize(),
            'callbacks' => $this->callbacks->count() === 0 ? null : $this->callbacks->jsonSerialize(),
            'security' => empty($this->security) ? null : $this->security,
            'servers' => $this->servers->count() === 0 ? $this->servers->jsonSerialize() : null,
        ]);
    }

    public function hasTag(string $tagName): bool
    {
        return in_array($tagName, $this->tags);
    }
}
