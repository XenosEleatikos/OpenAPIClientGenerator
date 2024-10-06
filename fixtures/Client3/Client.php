<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixture\Client3;

/**
 * # Pet Shop API
 *
 * Version: 1.0.0
 *
 * A sample Pet Store Server based on the OpenAPI 3.1
 *
 * This is a sample Pet Store Server based on the OpenAPI 3.1 specification.  You can find out more about Swagger at [https://swagger.io](https://swagger.io). In the third iteration of the pet store, we've switched to the design first approach!
 * You can now help us improve the API whether it's by making changes to the definition itself or to the code. That way, with time, we can improve the API in general, and expose some of the new features in OAS3.
 *
 * ## Contact
 *
 * [OpenAPI Specification v3.1.0](https://spec.openapis.org/oas/latest.html)
 *
 * E-mail: [apiteam@swagger.io](apiteam@swagger.io)
 *
 * ## Documentation
 *
 * Find more info here
 *
 * https://example.com
 *
 * ## Terms of service
 *
 * http://swagger.io/terms/
 *
 * ## License
 *
 * [MIT License](https://opensource.org/licenses/MIT)
 */
class Client
{
    public function __construct(
        private \Psr\Http\Client\ClientInterface $httpClient,
        private Config\Config $config,
    ) {
    }
}
