<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;

class Info implements JsonSerializable
{
    public function __construct(
        public string   $title,
        public string   $version,
        public ?string  $summary = null,
        public ?string  $description = null,
        public ?string  $termsOfService = null,
        public ?Contact $contact = null,
        public ?License $license = null,
    ) {
    }

    public static function make(stdClass $info): self
    {
        return new self(
            title: $info->title,
            version: $info->version,
            summary: $info->summary ?? null,
            description: $info->description ?? null,
            termsOfService: $info->termsOfService ?? null,
            contact: isset($info->contact) ? Contact::make($info->contact) : null,
            license : isset($info->license) ? License::make($info->license) : null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter((array)$this);
    }
}
