<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;
use function is_integer;
use function is_string;

class Schema implements JsonSerializable
{
    public function __construct(
        public SchemaTypes $type = new SchemaTypes(),
        public ?string $format = null,
        public SchemasOrReferences $properties = new SchemasOrReferences(),
        /** @var string[] */
        public array $required = [],
        /** @var mixed[] */
        public array $examples = [],
        public ?Xml $xml = null,
        public null|Schema|Reference $items = null,
        public ?string $description = null,
        /** @var array<int, mixed> */
        public array $enum = [],
        public mixed $default = null,
        public ?self $additionalProperties = null,
    ) {
        $this->properties = new SchemasOrReferences();
    }

    public function isEnum(): bool
    {
        return !empty($this->enum);
    }

    public function isEnumOfStrings(): bool
    {
        if (!$this->isEnum()) {
            return false;
        }

        foreach ($this->enum as $value) {
            if (!is_string($value)) {
                return false;
            }
        }

        return true;
    }

    public function isEnumOfIntegers(): bool
    {
        if (!$this->isEnum()) {
            return false;
        }

        foreach ($this->enum as $value) {
            if (!is_integer($value)) {
                return false;
            }
        }

        return true;
    }

    public static function make(stdClass $schema): self
    {
        $instance = new self(
            type: SchemaTypes::make($schema->type ?? []),
            format: $schema->format ?? null,
            properties: isset($schema->properties) ? SchemasOrReferences::make($schema->properties) : new SchemasOrReferences(),
            required: $schema->required ?? [],
            examples: $schema->examples ?? [],
            xml: isset($schema->xml) ? Xml::make($schema->xml) : null,
            items: isset($schema->items) ? Schema::makeSchemaOrReference($schema->items) : null,
            description: $schema->description ?? null,
            enum: $schema->enum ?? [],
            default: $schema->default ?? null,
            additionalProperties: isset($schema->additionalProperties) ? Schema::make($schema->additionalProperties) : null,
        );

        if (isset($schema->properties)) {
            $instance->properties = SchemasOrReferences::make($schema->properties);
        }

        return $instance;
    }

    public static function makeSchemaOrReference(stdClass $schemaOrReference): self|Reference
    {
        if (isset($schemaOrReference->{'$ref'})) {
            return Reference::make($schemaOrReference);
        } else {
            return Schema::make($schemaOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'type' => count($this->type) === 0 ? null : $this->type->jsonSerialize(),
            'format' => $this->format,
            'properties' => $this->properties->count() === 0 ? null : $this->properties->jsonSerialize(),
            'required' => empty($this->required) ? null : $this->required,
            'examples' => empty($this->examples) ? null : $this->examples,
            'xml' => isset($this->xml) ? $this->xml->jsonSerialize() : null,
            'items' => isset($this->items) ? $this->items->jsonSerialize() : null,
            'description' => $this->description,
            'enum' => empty($this->enum) ? null : $this->enum,
            'default' => $this->default,
            'additionalProperties' => $this->additionalProperties?->jsonSerialize(),
        ]);
    }
}
