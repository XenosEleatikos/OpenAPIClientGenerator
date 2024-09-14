<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use ArrayObject;
use JsonSerializable;
use stdClass;

use function array_map;
use function count;

/** @extends ArrayObject<int, Server> */
class Servers extends ArrayObject implements JsonSerializable
{
    /** @param Server[] $servers */
    public function __construct(
        array $servers = [new Server(url: '/')]
    ) {
        parent::__construct($servers);
    }

    /** @param stdClass[] $servers */
    public static function make(array $servers): self
    {
        return new self(
            array_map(
                fn (stdClass $server): Server => Server::make($server),
                $servers
            )
        );
    }

    /** @return array<int, stdClass> */
    public function jsonSerialize(): array
    {
        $array = $this->getArrayCopy();
        if (
            count($array) === 1
            && $array[0]->url === '/'
            && $array[0]->description === null
            && $array[0]->variables->count() === 0
        ) {
            return [];
        }

        return array_map(
            fn (Server $server) => $server->jsonSerialize(),
            $array
        );
    }
}
