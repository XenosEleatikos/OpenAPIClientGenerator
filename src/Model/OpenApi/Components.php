<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;
use function array_filter;

class Components implements JsonSerializable
{
    public function __construct(
        public Schemas                     $schemas = new Schemas(),
        public ResponsesOrReferences       $responses = new ResponsesOrReferences(),
        public ParametersOrReferences      $parameters = new ParametersOrReferences(),
        public ExamplesOrReferences        $examples = new ExamplesOrReferences(),
        public RequestBodiesOrReferences   $requestBodies = new RequestBodiesOrReferences(),
        public HeadersOrReferences         $headers = new HeadersOrReferences(),
        public SecuritySchemesOrReferences $securitySchemes = new SecuritySchemesOrReferences(),
    ) {
    }

    public static function make(stdClass $component): self
    {
        return new self(
            schemas: isset($component->schemas) ? Schemas::make($component->schemas) : new Schemas(),
            responses: isset($component->responses) ? ResponsesOrReferences::make($component->responses) : new ResponsesOrReferences(),
            parameters: isset($component->parameters) ? ParametersOrReferences::make($component->parameters) : new ParametersOrReferences(),
            examples: isset($component->examples) ? ExamplesOrReferences::make($component->examples) : new ExamplesOrReferences(),
            requestBodies: isset($component->requestBodies) ? RequestBodiesOrReferences::make($component->requestBodies) : new RequestBodiesOrReferences(),
            headers: isset($component->headers) ? HeadersOrReferences::make($component->headers) : new HeadersOrReferences(),
            securitySchemes: isset($component->securitySchemes) ? SecuritySchemesOrReferences::make($component->securitySchemes) : new SecuritySchemesOrReferences(),
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

