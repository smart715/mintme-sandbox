<?php declare(strict_types = 1);

namespace App\Tests\Exchange;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use PHPUnit\Framework\TestCase;

class MarketTest extends TestCase
{
    /** @dataProvider isTokenMarketProvider */
    public function testIsTokenMarket(TradebleInterface $base, TradebleInterface $quote, bool $isTokenMarket): void
    {
        $market = new Market($base, $quote);

        $this->assertEquals($isTokenMarket, $market->isTokenMarket());
    }

    public function isTokenMarketProvider(): array
    {
        return [
            [$this->mockToken(), $this->mockToken(), true],
            [$this->mockCrypto(), $this->mockToken(), true],
            [$this->mockToken(), $this->mockCrypto(), true],
            [$this->mockCrypto(), $this->mockCrypto(), false],
        ];
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
