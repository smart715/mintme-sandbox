<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Utils\Validator\MarketValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketValidatorTest extends TestCase
{
    /** @dataProvider orderProvider */
    public function testValid(
        string $baseSymbol,
        string $quoteSymbol,
        bool $isBaseCrypto,
        bool $isQuoteCrypto,
        bool $result,
        bool $tokenHasMarket = false
    ): void {
        $base = $isBaseCrypto
            ? $this->mockCrypto($baseSymbol)
            : $this->mockToken($baseSymbol, $tokenHasMarket);
        $quote = $isQuoteCrypto
            ? $this->mockCrypto($quoteSymbol)
            : $this->mockToken($quoteSymbol, $tokenHasMarket);

        $marketValidator =  new MarketValidator(new Market($base, $quote));

        $this->assertEquals($result, $marketValidator->validate());
        $this->assertEquals('Invalid Market', $marketValidator->getMessage());
    }

    public function orderProvider(): array
    {
        return [
            ['BTC', 'WEB', true, true, true],
            ['WEB', 'BTC', true, true, true],
            ['BTC', 'BTC', true, true, false],
            ['WEB', 'WEB', true, true, false],
            ['WEB', 'foo', true, false, true, true],
            ['BTC', 'foo', true, false, true, true],
            ['BTC', 'foo', true, false, false],
            ['foo', 'WEB', false, true, false],
            ['foo', 'bar', false, false, false],
            ['foo', 'BTC', false, false, false],
            ['foo', 'foo', false, false, false],
        ];
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    /** @return MockObject|Token */
    private function mockToken(string $symbol, bool $hasMarket): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn($symbol);
        $token->method('getCryptoSymbol')->willReturn("WEB");
        $token->method('containsExchangeCrypto')->willReturn($hasMarket);

        return $token;
    }
}
