<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Components implements JsonSerializable
{
    public function __construct(
        public Schemas         $schemas = new Schemas(),
        public Responses       $responses = new Responses(),
        public Parameters      $parameters = new Parameters(),
        public Examples        $examples = new Examples(),
        public RequestBodies   $requestBodies = new RequestBodies(),
        public Headers         $headers = new Headers(),
        public SecuritySchemes $securitySchemes = new SecuritySchemes(),
    ) {
    }

    public static function make(stdClass $component): self
    {
        return new self(
            schemas: isset($component->schemas) ? Schemas::make($component->schemas) : new Schemas(),
            responses: isset($component->responses) ? Responses::make($component->responses) : new Responses(),
            parameters: isset($component->parameters) ? Parameters::make($component->parameters) : new Parameters(),
            examples: isset($component->examples) ? Examples::make($component->examples) : new Examples(),
            requestBodies: isset($component->requestBodies) ? RequestBodies::make($component->requestBodies) : new RequestBodies(),
            headers: isset($component->headers) ? Headers::make($component->headers) : new Headers(),
            securitySchemes: isset($component->securitySchemes) ? SecuritySchemes::make($component->securitySchemes) : new SecuritySchemes(),
        );
    }

    public function hasComponents(): bool
    {
        return
            $this->schemas->count() > 0
            || $this->responses->count() > 0
            || $this->parameters->count() > 0
            || $this->examples->count() > 0
            || $this->requestBodies->count() > 0
            || $this->headers->count() > 0
            || $this->securitySchemes->count() > 0;
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'schemas' => $this->schemas->count() === 0 ? null : $this->schemas->jsonSerialize(),
            'responses' => $this->responses->count() === 0 ? null : $this->schemas->jsonSerialize(),
            'parameters' => $this->parameters->count() === 0 ? null : $this->schemas->jsonSerialize(),
            'examples' => $this->examples->count() === 0 ? null : $this->schemas->jsonSerialize(),
            'requestBodies' => $this->requestBodies->count() === 0 ? null : $this->requestBodies->jsonSerialize(),
            'headers' => $this->headers->count() === 0 ? null : $this->headers->jsonSerialize(),
            'securitySchemes' => $this->securitySchemes->count() === 0 ? null : $this->securitySchemes->jsonSerialize(),
        ]);
    }
}
