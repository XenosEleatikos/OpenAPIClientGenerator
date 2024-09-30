<?php

namespace Xenos\OpenApiClientGeneratorTest\Generator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApi\Model\Tags;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ClientGenerator;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;

use function sys_get_temp_dir;
use function time;

class ClientGeneratorTest extends TestCase
{
    private ClientGenerator $clientGenerator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/openApiClient/' . time();
        $config = new Config(namespace: 'Xenos\OpenApiClientGeneratorFixture', directory: $this->tmpDir);

        $this->clientGenerator = new ClientGenerator(
            $config,
            new Printer(new PsrPrinter())
        );
    }

    public function testGenerate(): void
    {
        $openApi = new OpenAPI(
            openapi: Version::make('3.1.0'),
            info: new Info('Pet Shop API', '1.0.0'),
            tags: new Tags(
                [
                    new Tag(name: 'pet'),
                    new Tag(name: 'store'),
                    new Tag(name: 'user'),
                ]
            )
            // paths: new Paths(
            //     [
            //         '/pet' => new PathItem(get: new Operation(tags: ['pet'])),
            //         '/store' => new PathItem(get: new Operation(tags: ['store'])),
            //         '/user' => new PathItem(get: new Operation(tags: ['user'])),
            //     ]
            // ),
        );
        $this->clientGenerator->generate($openApi);

        $file = 'Client.php';

        self::assertFileExists($this->tmpDir . '/src/' . $file);
        self::assertFileEquals(
            __DIR__ . '/../../../fixtures/' . $file,
            $this->tmpDir . '/src/' . $file
        );
    }
}
