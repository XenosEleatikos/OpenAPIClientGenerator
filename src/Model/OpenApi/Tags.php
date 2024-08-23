<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;

/** @implements ArrayObject<int, Tag> */
class Tags extends ArrayObject implements JsonSerializable
{
    public static function make(array $tags): self
    {
        return new self(array_map(
            fn(stdClass $tag): Tag => Tag::make($tag),
            $tags
        ));
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn(Tag $tag) => $tag->jsonSerialize(),
            $this->getArrayCopy()
        );
    }
}
