<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ApiGenerator;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApi\Model\ExternalDocumentation;
use Xenos\OpenApi\Model\Info;
use Xenos\OpenApi\Model\OpenAPI;
use Xenos\OpenApi\Model\Operation;
use Xenos\OpenApi\Model\PathItem;
use Xenos\OpenApi\Model\Paths;
use Xenos\OpenApi\Model\Tag;
use Xenos\OpenApi\Model\Tags;
use Xenos\OpenApi\Model\Version;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ApiGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\ClassCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodCommentGenerator;
use Xenos\OpenApiClientGenerator\Generator\ApiGenerator\MethodNameGenerator;
use Xenos\OpenApiClientGenerator\Generator\Printer\Printer;
use Xenos\OpenApiClientGeneratorTestHelper\TmpDir;

class ApiGeneratorTest extends TestCase
{
    #[DataProvider('provideDataForTestGenerateClassComment')]
    public function testGenerateClassComment(string $className, OpenAPI $openAPI, string $tag): void
    {
        $tmpDir = new TmpDir('ApiGeneratorTest\TestGenerateClassComment');
        $apiGenerator = $this->getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: $tag);

        self::assertFileExists($tmpDir->getGeneratedFilePath('Api/' . $className . '.php'));

        $reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\\' . $className);
        $reflectionClassFixture = $tmpDir->reflectFixture('Api\\' . $className);

        self::assertIsString(
            actual: $reflectionClassGenerated->getDocComment(),
            message: 'Expected that class ' . $className . ' has a doc comment'
        );

        self::assertSame(
            expected: $reflectionClassFixture->getDocComment(),
            actual: $reflectionClassGenerated->getDocComment(),
            message: 'Doc comment for class ' . $className . ' is not as expected'
        );
    }

    public static function provideDataForTestGenerateClassComment(): array
    {
        return [
            'Undeclared tag' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'tag' => 'Test1',
            ],
            'Declared tag' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(name: 'Test1')
                    ]),
                ),
                'tag' => 'Test1',
            ],
            'Declared tag with description' => [
                'className' => 'Test2Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test2',
                            description: 'Some description'
                        )
                    ]),
                ),
                'tag' => 'Test2',
            ],
            'Declared tag with description and external docs (without description)' => [
                'className' => 'Test3Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test3',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                            )
                        )
                    ]),
                ),
                'tag' => 'Test3',
            ],
            'Declared tag with description and external docs (with empty description)' => [
                'className' => 'Test3Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test3',
                            description: '',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                            )
                        )
                    ]),
                ),
                'tag' => 'Test3',
            ],
            'Declared tag with description and external docs (with description)' => [
                'className' => 'Test4Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    tags: new Tags([
                        new Tag(
                            name: 'Test4',
                            description: 'Some description',
                            externalDocs: new ExternalDocumentation(
                                url: 'https://example.com/docs',
                                description: 'Find more information here'
                            )
                        )
                    ]),
                ),
                'tag' => 'Test4',
            ],
        ];
    }

    #[DataProvider('provideDataForTestGenerateApiMethods')]
    public function testGenerateApiMethods(
        string $className,
        OpenAPI $openAPI,
        string $tag
    ): void {
        $tmpDir = new TmpDir('ApiGeneratorTest\TestGenerateApiMethods');
        $apiGenerator = $this->getApiGenerator($tmpDir);

        $apiGenerator->generate(openAPI: $openAPI, tag: $tag);

        //$reflectionClassGenerated = $tmpDir->reflectGeneratedClass('Api\\' . $className);
        self::assertFileExists($tmpDir->getGeneratedFilePath('Api/' . $className . '.php'));
        self::assertSame(
            $tmpDir->getFixtureFile('Api/' . $className . '.php'),
            $tmpDir->getGeneratedFile('Api/' . $className . '.php')
        );
    }

    public static function provideDataForTestGenerateApiMethods(): array
    {
        return [
            'empty API' => [
                'className' => 'Test1Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                ),
                'tag' => 'Test1',
            ],
            'API with one declared tag' => [
                'className' => 'Test2Api',
                'openAPI' => new OpenAPI(
                    openapi: Version::make('3.1.0'),
                    info: new Info('Pet Shop API', '1.0.0'),
                    paths: new Paths(
                        [
                            '/pet' => new PathItem(
                                get: new Operation(
                                    tags: ['Test2']
                                ),
                            ),
                        ]
                    ),
                ),
                'tag' => 'Test2',
            ],
        ];
    }

    private function getApiGenerator(TmpDir $tmpDir): ApiGenerator
    {
        return new ApiGenerator(
            config: $tmpDir->makeConfig(),
            printer: new Printer(new PsrPrinter()),
            methodNameGenerator: new MethodNameGenerator(),
            classCommentGenerator: new ClassCommentGenerator(),
            methodCommentGenerator: new MethodCommentGenerator()
        );
    }
}
