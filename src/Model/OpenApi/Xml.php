<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;
use function array_filter;

class Xml implements JsonSerializable
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $prefix = null,
        public bool $attribute = false,
        public bool $wrapped = false,
    ) {
    }

    public static function make(stdClass $xml): self
    {
        return new self(...(array)$xml);
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'prefix' => $this->prefix,
            'attribute' => $this->attribute ? true : null,
            'wrapped' => $this->wrapped ? true : null,
        ]);
    }
}
