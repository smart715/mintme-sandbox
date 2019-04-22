<?php declare(strict_types = 1);

namespace App\Tests\Utils;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Config\Config;
use App\Manager\CryptoManagerInterface;
use App\Utils\Converter\TokenNameConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenNameConverterTest extends TestCase
{
    /**
     * @dataProvider convertProvider
     */
    public function testConvert(string $tokenId, int $offset, string $tokenName): void
    {
        $converter = new TokenNameConverter(
            $this->mockCryptoManager($this->mockCrypto('WEB')),
            $this->mockConfig($offset)
        );

        $this->assertEquals($tokenName, $converter->convert($this->mockToken($tokenId)));
    }

    public function convertProvider(): array
    {
        return [
            [ 1, 0, 'TOK000000000001' ],
            [ 123, 0, 'TOK000000000123' ],
            [ 321, 0, 'TOK000000000321' ],
            [ 99999999999999, 0, 'TOK99999999999999' ],
            [ -1, 0, 'TOK0000000000-1' ],
            [ 'WEB', 0, 'WEB' ],
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

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(?Crypto $crypto): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);

        $manager
            ->method('findBySymbol')
            ->willReturnCallback(function (string $symbol) use ($crypto) {
                return $crypto->getSymbol() == $symbol
                    ? $crypto
                    : null;
            });

        return $manager;
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto->method('getSymbol')->willReturn($symbol);
        $crypto->method('getName')->willReturn($symbol);

        return $crypto;
    }

    /**
     * @return Token|MockObject
     */
    private function mockToken(string $value): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('getId')->willReturn($value);
        $token->method('getName')->willReturn($value);

        return $token;
    }
}
