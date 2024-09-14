<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Parameter implements JsonSerializable
{
    public bool $explode;

    public function __construct(
        public string  $name,
        public ParameterLocation    $in,
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

    public static function make(stdClass $parameter): self
    {
        return new self(
            name: $parameter->name,
            in: ParameterLocation::from($parameter->in),
            description: $parameter->description ?? null,
            required: $parameter->required ?? false,
            deprecated: $parameter->deprecated ?? false,
            allowEmptyValues: $parameter->allowEmptyValues ?? false,
            style: $parameter->style ?? null,
            explode: $parameter->explode ?? null,
            allowReserved: $parameter->allowReserved ?? false,
            schema: isset($parameter->schema) ? Schema::make($parameter->schema) : null,
            example: $parameter->example ?? null,
            examples: isset($parameter->examples) ? ExamplesOrReferences::make($parameter->examples) : new ExamplesOrReferences(),
        );
    }

    public static function makeParameterOrReference(stdClass $parameterOrReference): self|Reference
    {
        if (isset($parameterOrReference->{'$ref'})) {
            return Reference::make($parameterOrReference);
        } else {
            return self::make($parameterOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'name' => $this->name,
            'in' => $this->in->jsonSerialize(),
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
