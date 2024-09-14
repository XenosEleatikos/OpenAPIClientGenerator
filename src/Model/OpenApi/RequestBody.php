<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;

use stdClass;

use function array_filter;

class RequestBody implements JsonSerializable
{
    public function __construct(
        public ?string    $description = null,
        public MediaTypes $content = new MediaTypes(),
        public bool $required = false,
    ) {
    }

    public static function make(stdClass $requestBody): self
    {
        return new self(
            description: $requestBody->description ?? null,
            content: isset($requestBody->content) ? MediaTypes::make($requestBody->content) : new MediaTypes(),
            required: $requestBody->required ?? false,
        );
    }

    public static function makeRequestBodyOrReference(stdClass $requestBodyOrReference): self|Reference
    {
        if (isset($requestBodyOrReference->{'$ref'})) {
            return RequestBody::make($requestBodyOrReference);
        } else {
            return self::make($requestBodyOrReference);
        }
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'description' => $this->description,
            'content' => $this->content->jsonSerialize(),
            'required' => $this->required ?: null,
        ]);
    }
}
