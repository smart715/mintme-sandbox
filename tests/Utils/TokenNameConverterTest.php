<?php declare(strict_types = 1);

namespace App\Tests\Utils;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Config\Config;
use App\Utils\Converter\TokenNameConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenNameConverterTest extends TestCase
{
    /**
     * @dataProvider convertProvider
     */
    public function testConvert(int $tokenId, int $offset, string $tokenName): void
    {
        $converter = new TokenNameConverter(
            $this->mockConfig($offset)
        );

        $this->assertEquals($tokenName, $converter->convert($this->mockToken($tokenId, $tokenName)));
    }

    public function convertProvider(): array
    {
        return [
            [ 1, 0, 'TOK000000000001' ],
            [ 123, 0, 'TOK000000000123' ],
            [ 321, 0, 'TOK000000000321' ],
            [ 99999999999999, 0, 'TOK99999999999999' ],
            [ -1, 0, 'TOK0000000000-1' ],
            [ 1, 5, 'TOK000000000006' ],
        ];
    }

    /** @return MockObject|Config */
    private function mockConfig(int $offset): Config
    {
        $config = $this->createMock(Config::class);

        $config->method('getOffset')->willReturn($offset);

        return $config;
    }

    /**
     * @return Token|MockObject
     */
    private function mockToken(int $value, string $name): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('getId')->willReturn($value);
        $token->method('getName')->willReturn($name);

        return $token;
    }
}
