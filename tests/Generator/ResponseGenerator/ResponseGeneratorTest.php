<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ResponseGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\Components;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Response;
use Xenos\OpenApi\Model\Responses;
use Xenos\OpenApi\Model\ResponsesOrReferences;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\SchemaClassNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\SchemaGenerator\TypeHintGenerator;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

#[RunTestsInSeparateProcesses]
class ResponseGeneratorTest extends TestCase
{
    private TmpDir $tmpDir;
    private ResponseGenerator $responseGenerator;

    protected function setUp(): void
    {
        $this->tmpDir = new TmpDir();
        $config = $this->tmpDir->makeConfig();
        $printer = new Printer(new PsrPrinter());

        $this->responseGenerator = new ResponseGenerator(
            config: $config,
            printer: $printer,
            typeHintGenerator: new TypeHintGenerator(
                config: $config,
                schemaClassNameGenerator: new SchemaClassNameGenerator(),
            ),
            responseClassNameGenerator: new ResponseClassNameGenerator(
                config: $config,
            )
        );
    }

    /** @param string[] $expectedResponses */
    #[DataProvider('provideOpenApiSpecificationsWithResponses')]
    public function testFindResponsesAndCreateFiles(
        OpenAPI $openAPI,
        array $expectedResponses,
    ): void {
        $this->responseGenerator->generate($openAPI);

        self::assertSame(
            expected: $expectedResponses,
            actual: $this->tmpDir->list('Response'),
            message: 'Expected files have not been generated.'
        );
    }

    /** @return array<string, array{openAPI: OpenAPI, expectedResponses: string[]}> */
    public static function provideOpenApiSpecificationsWithResponses(): array
    {
        return [
            'empty API' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'expectedResponses' => [],
            ],
            'one response in components' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'NotFound' => new Response(
                                description: 'Entity not found',
                            )
                        ])
                    ),
                ),
                'expectedResponses' => ['NotFound.php'],
            ],
            'two responses in components' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    components: new Components(
                        responses: new Responses([
                            'NotFound' => new Response(
                                description: 'Entity not found',
                            ),
                            'GeneralError' => new Response(
                                description: 'An error occurred',
                            ),
                        ])
                    ),
                ),
                'expectedResponses' => ['GeneralError.php', 'NotFound.php'],
            ],
            'one anonymous response in operation' => [
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths([
                        '/pets' => new PathItem(
                            get: new Operation(
                                responses: new ResponsesOrReferences([ // @phpstan-ignore-line
                                    '200' => new Response(description: 'successful operation')
                                ])
                            )
                        )
                    ]),
                ),
                'expectedResponses' => ['GetPets200Response.php'],
            ],
        ];
    }
}
