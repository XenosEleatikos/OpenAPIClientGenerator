<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorFixtureTest\Client1;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Xenos\OpenApiClientGeneratorFixture\Client1\Api\PetApi;
use Xenos\OpenApiClientGeneratorFixture\Client1\Api\StoreApi;
use Xenos\OpenApiClientGeneratorFixture\Client1\Api\UserApi;
use Xenos\OpenApiClientGeneratorFixture\Client1\Client;
use Xenos\OpenApiClientGeneratorFixture\Client1\Config\Config;
use Xenos\OpenApiClientGeneratorFixture\Client1\Config\Server;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client(
            $this->createStub(ClientInterface::class),
            new Config(Server::SERVER_0)
        );
    }

    public function testClientReturnsApi(): void
    {
        self::assertInstanceOf(PetApi::class, $this->client->pet());
        self::assertInstanceOf(StoreApi::class, $this->client->store());
        self::assertInstanceOf(UserApi::class, $this->client->user());
    }

    public function testClientReturnsSingleton(): void
    {
        self::assertSame($this->client->pet(), $this->client->pet());
        self::assertSame($this->client->store(), $this->client->store());
        self::assertSame($this->client->user(), $this->client->user());
    }
}
