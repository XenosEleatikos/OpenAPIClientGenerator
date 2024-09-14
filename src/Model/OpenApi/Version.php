<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;

use function array_map;
use function explode;

class Version implements JsonSerializable
{
    private const SEPARATOR = '.';

    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
    ) {
    }

    public static function make(string $version): self
    {
        return new self(...array_map(fn (string $number) => (int)$number, explode(self::SEPARATOR, $version)));
    }

    public function jsonSerialize(): string
    {
        return $this->major . self::SEPARATOR . $this->minor . self::SEPARATOR . $this->patch;
    }
}
