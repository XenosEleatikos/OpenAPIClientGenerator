<?php

declare(strict_types=1);

namespace OpenApiClientGenerator\Model\OpenApi;

use JsonSerializable;
use stdClass;

use function array_filter;
use function array_shift;
use function explode;

class OpenAPI implements JsonSerializable
{
    public function __construct(
        public Version     $openapi,
        public Info $info,
        public string $jsonSchemaDialect = 'https://spec.openapis.org/oas/3.1/dialect/base',
        public Servers $servers = new Servers([new Server(url: '/')]),
        public Paths $paths = new Paths(),
        public Webhooks $webhooks = new Webhooks(),
        public Components $components = new Components(),
        /** @var array<string, string[]> */
        public array $security = [],
        public Tags $tags = new Tags(),
        public ?ExternalDocumentation $externalDocs = null,
    ) {
    }

    public static function make(stdClass $openAPI): self
    {
        return new self(
            openapi: Version::make($openAPI->openapi),
            info: Info::make($openAPI->info),
            servers: isset($openAPI->servers) ? Servers::make($openAPI->servers) : new Servers(),
            paths: isset($openAPI->paths) ? Paths::make($openAPI->paths) : new Paths(),
            webhooks: isset($openAPI->webhooks) ? Webhooks::make($openAPI->webhooks) : new Webhooks(),
            components: isset($openAPI->components) ? Components::make($openAPI->components) : new Components(),
            security: $openAPI->security ?? [],
            tags: isset($openAPI->tags) ? Tags::make($openAPI->tags) : new Tags(),
            externalDocs: isset($openAPI->externalDocs) ? ExternalDocumentation::make($openAPI->externalDocs) : null,
        );
    }

    public function jsonSerialize(): stdClass
    {
        return (object)array_filter([
            'openapi' => $this->openapi->jsonSerialize(),
            'info' => $this->info->jsonSerialize(),
            'servers' => $this->servers->count() === 0 ? null : $this->servers->jsonSerialize(),
            'paths' => $this->paths->count() === 0 ? null : $this->paths->jsonSerialize(),
            'webhooks' => $this->webhooks->count() === 0 ? null : $this->webhooks->jsonSerialize(),
            'components' => $this->components->hasComponents() ? $this->components->jsonSerialize() : null,
            'security' => empty($this->security) ? null : $this->security,
            'tags' => $this->tags->count() === 0 ? null : $this->tags,
            'externalDocs' => $this->externalDocs?->jsonSerialize(),
        ]);
    }

    // @todo error handling
    public function resolveReference(Reference $reference): null|Schema|Response|Parameter|Example|RequestBody|Header|SecurityScheme|Link
    {
        if ($reference->ref[0] === '#') {
            $path = explode('/', $reference->ref);
            array_shift($path);
            $found = self::get($this, $path);
        }

        return $found ?? null;
    }

    // @todo Implement links, callbacks and path items
    // @todo Implement external urls
    /** @param string[] $path */
    private static function get(object $object, array $path): Schema|Response|Parameter|Example|RequestBody|Header|SecurityScheme|Link
    {
        $property = array_shift($path);

        return empty($path)
            ? $object->$property ?? $object[$property] // @phpstan-ignore-line
            : self::get($object->$property ?? $object[$property], $path); // @phpstan-ignore-line
    }
}
