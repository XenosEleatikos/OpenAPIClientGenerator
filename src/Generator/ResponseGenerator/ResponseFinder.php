<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGenerator\Generator\ResponseGenerator;

use Xenos\OpenApi\Model\Method;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Response;

readonly class ResponseFinder
{
    public function __construct(
        private ResponseClassNameGenerator $responseClassNameGenerator,
    ) {
    }

    /** @return array<string, Response> */
    public function findResponses(OpenAPI $openAPI): array
    {
        foreach ($openAPI->components->responses as $name => $response) {
            $responses[(string)$this->responseClassNameGenerator->fromComponentsKey((string)$name)] = $response;
        }

        foreach ($this->findAnonymousResponses($openAPI) as $fqcn => $response) {
            $responses[$fqcn] = $response;
        }

        return $responses ?? [];
    }

    /** @return array<string, Response> */
    private function findAnonymousResponses(OpenAPI $openAPI): array
    {
        foreach ($openAPI->paths as $endpoint => $pathItem) {
            foreach ($pathItem->getAllOperations() as $method => $operation) {
                foreach ($operation->responses as $statusCode => $response) {
                    if ($response instanceof Response) {
                        $anonymousResponses[(string)$this->responseClassNameGenerator
                            ->fromOperation(
                                method: Method::from($method),
                                endpoint: $endpoint,
                                operation: $operation,
                                statusCode: (string)$statusCode
                            )] = $response;
                    }
                }
            }
        }

        return $anonymousResponses ?? [];
    }
}
