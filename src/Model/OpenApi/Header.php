<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Header implements JsonSerializable
{
    public bool $explode;

    public function __construct(
        public ?string              $description = null,
        public bool                 $required = false,
        public bool                 $deprecated = false,
        public bool                 $allowEmptyValues = false,
        public ?string              $style = null,
        ?bool                 $explode = null,
        public bool                 $allowReserved = false,
        public ?Schema              $schema = null,
        public mixed                $example = null,
        public ExamplesOrReferences $examples = new ExamplesOrReferences(),
    ) {
        $this->explode = $explode ?? $this->getExplodeDefaultValue();
    }

    public static function make(stdClass $header): self
    {
        return new self(
            description: $header->description ?? null,
            required: $header->required ?? false,
            deprecated: $header->deprecated ?? false,
            allowEmptyValues: $header->allowEmptyValues ?? false,
            style: $header->style ?? null,
            explode: $header->explode ?? null,
            allowReserved: $header->allowReserved ?? false,
            schema: isset($header->schema) ? Schema::make($header->schema) : null,
            example: $header->example ?? null,
            examples: isset($header->examples) ? ExamplesOrReferences::make($header->examples) : new ExamplesOrReferences(),
        );
    }

    public static function makeHeaderOrReference(stdClass $headerOrReference): self|Reference
    {
        if (isset($headerOrReference->{'$ref'})) {
            return Reference::make($headerOrReference);
        } else {
            return self::make($headerOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'description' => $this->description,
            'required' => $this->required ?: null,
            'deprecated' => $this->deprecated ?: null,
            'allowEmptyValues' => $this->allowEmptyValues ?: null,
            'style' => $this->style,
            'explode' => $this->explode !== $this->getExplodeDefaultValue() ? $this->explode : null,
            'allowReserved' => $this->allowReserved ?: null,
            'schema' => $this->schema?->jsonSerialize(),
            'example' => $this->example,
            'examples' => $this->examples->count() === 0 ? null : $this->examples->jsonSerialize(),
        ]);
    }

    private function getExplodeDefaultValue(): bool
    {
        return $this->style === 'form';
    }
}
