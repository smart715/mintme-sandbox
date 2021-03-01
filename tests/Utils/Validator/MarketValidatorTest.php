<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Utils\Validator\MarketValidator;
use App\Utils\Validator\MinOrderValidator;
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
        bool $result
    ): void {
        $base = $isBaseCrypto
            ? $this->mockCrypto($baseSymbol)
            : $this->mockToken($baseSymbol);
        $quote = $isQuoteCrypto
            ? $this->mockCrypto($quoteSymbol)
            : $this->mockToken($quoteSymbol);

        $marketValidator =  new MarketValidator(new Market($base, $quote));
        $this->assertEquals($result, $marketValidator->validate());
    }

    public function orderProvider(): array
    {
        return [
            ['BTC', 'WEB', true, true, true],
            ['WEB', 'BTC', true, true, false],
            ['BTC', 'BTC', true, true, false],
            ['WEB', 'WEB', true, true, false],
            ['WEB', 'foo', true, false, true],
            ['foo', 'WEB', false, true, false],
            ['foo', 'bar', false, false, false],
            ['foo', 'foo', false, false, false],
        ];
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol): Crypto
    {
        $tokenMock = $this->createMock(Crypto::class);
        $tokenMock->method('getSymbol')->willReturn($symbol);

        return $tokenMock;
    }

    /** @return MockObject|Token */
    private function mockToken(string $symbol): Token
    {
        $tokenMock = $this->createMock(Token::class);
        $tokenMock->method('getSymbol')->willReturn($symbol);

        return $tokenMock;
    }
}
