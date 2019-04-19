<?php declare(strict_types = 1);

namespace App\Tests\Utils;

use App\Utils\Converter\TokenNameNormalizer;
use PHPUnit\Framework\TestCase;

class TokenNameNormalizerTest extends TestCase
{
    /** @var TokenNameNormalizer */
    private $tokenNameNormalizer;

    public function setUp(): void
    {
        $this->tokenNameNormalizer = new TokenNameNormalizer();
        parent::setUp();
    }


    /**
     * @dataProvider parserProvider
     */
    public function testParser(string $name, string $parsedName): void
    {
        $this->assertEquals($parsedName, $this->tokenNameNormalizer->parse($name));
    }

    public function parserProvider(): array
    {
        return [
            ['test', 'test'],
            [' test', 'test'],
            ['test  123', 'test 123'],
            ['test--123', 'test-123'],
            [' - - - t--e st', 't-e st'],
            [' te s  t ', 'te s t'],
            [' test--', 'test'],
            [' test-1--  1', 'test-1-1'],
            ['- tes t-1--  1', 'tes t-1-1'],
        ];
    }


    /**
     * @dataProvider dashedProvider
     */
    public function testDashed(string $name, string $parsedName): void
    {
        $this->assertEquals($parsedName, $this->tokenNameNormalizer->dashed($name));
    }

    public function dashedProvider(): array
    {
        return [
            ['test', 'test'],
            ['test 123', 'test-123'],
            ['test--123', 'test-123'],
            [' test', 'test'],
            [' test ', 'test'],
            [' test--', 'test'],
            [' test-1--  1', 'test-1-1'],
            ['- tes t-1--  1', 'tes-t-1-1'],
        ];
    }
}
