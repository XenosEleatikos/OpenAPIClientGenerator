<?php

declare(strict_types=1);

namespace Xenos\OpenApiClientGeneratorTest\Generator\ResponseGenerator;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Xenos\OpenApiClientGenerator\Generator\Config\Config;
use Xenos\OpenApiClientGenerator\Generator\ResponseGenerator\ResponseClassNameGenerator;

use function array_map;
use function var_export;

class ResponseClassNameGeneratorTest extends TestCase
{
    private ResponseClassNameGenerator $responseClassNameGenerator;

    protected function setUp(): void
    {
        $this->responseClassNameGenerator = new ResponseClassNameGenerator(
            new Config('PetShop', '/test')
        );
    }

    #[DataProvider('provideComponentNamesAndClassNames')]
    public function testCreateResponseClassNameFromComponentsKey(
        string $componentsKey,
        string $expectedClassName,
    ): void {
        self::assertSame(
            $expectedClassName,
            (string)$this->responseClassNameGenerator->createResponseClassNameFromComponentsKey($componentsKey)
        );
        self::assertIsValidClassName($expectedClassName);
    }

    #[DataProvider('provideReferencePathsAndClassNames')]
    public function testCreateResponseClassNameFromReferencePath(
        string $referencePath,
        string $expectedClassName,
    ): void {
        self::assertSame(
            $expectedClassName,
            (string)$this->responseClassNameGenerator->createResponseClassNameFromReferencePath($referencePath)
        );
        self::assertIsValidClassName($expectedClassName);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function provideReferencePathsAndClassNames(): array
    {
        return array_map(
            callback: fn (array $componentNameAndClassName): array => ['components/responses/' . $componentNameAndClassName[0], $componentNameAndClassName[1]],
            array: self::provideComponentNamesAndClassNames(),
        );
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function provideComponentNamesAndClassNames(): array
    {
        return [
            'one word class name upper case' => ['Pet', 'PetShop\Response\Pet'],
            'one word class name lower case' => ['pet', 'PetShop\Response\Pet'],
            'one word class name lower case with number in the end' => ['pet123', 'PetShop\Response\Pet123'],
            'lower dot case' => ['pet.shop', 'PetShop\Response\PetShop'],
            'lower kebap case' => ['class-name', 'PetShop\Response\ClassName'],
            'lower snake case' => ['test_class', 'PetShop\Response\TestClass'],
            'mixed case' => ['my-dog.pet_shop', 'PetShop\Response\MyDogPetShop'],
            'starting with dot' => ['.pet', 'PetShop\Response\Pet'],
            'starting with underscore' => ['_pet', 'PetShop\Response\Pet'],
            'starting with dash' => ['-pet', 'PetShop\Response\Pet']
        ];
    }

    #[DataProvider('provideInvalidComponentKeys')]
    public function testCreateResponseClassNameFromComponentsKeyThrowsInvalidArgumentException(
        string $componentsKey,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component key must be a string matching the regular expression "^[a-zA-Z0-9._-]+$", ' . var_export($componentsKey, true) . ' given.');

        $this->responseClassNameGenerator->createResponseClassNameFromComponentsKey($componentsKey);
    }

    /** @return array<int, array<int, string>>*/
    public static function provideInvalidComponentKeys(): array
    {
        return [
            ['two words'],
            ['@'],
            ['#'],
            ['%'],
            [PHP_EOL],
            ['ä'],
            ['ö'],
            ['ü'],
            ['ß'],
        ];
    }

    private static function assertIsValidClassName(string $expectedClassName): void
    {
        self::assertMatchesRegularExpression(
            pattern: '/^[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*(\\\\[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*)*$/',
            string: $expectedClassName,
            message: ResponseClassNameGenerator::class . ' generated an invalid class name',
        );
    }
}
