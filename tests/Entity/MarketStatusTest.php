<?php declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\MarketInfo;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MarketStatusTest extends TestCase
{
    /** @dataProvider gettersDataProvider */
    public function testGetters(Crypto $base, TradebleInterface $quote, MarketInfo $mi, string $res): void
    {
        $ms = new MarketStatus(
            $base,
            $quote,
            $mi
        );

        $o = $ms->getOpenPrice();
        $l = $ms->getLastPrice();
        $v = $ms->getDayVolume();

        $this->assertEquals($mi->getOpen()->getAmount(), $o->getAmount());
        $this->assertEquals($res, $o->getCurrency()->getCode());

        $this->assertEquals($mi->getLast()->getAmount(), $l->getAmount());
        $this->assertEquals($res, $l->getCurrency()->getCode());

        $this->assertEquals($mi->getVolume()->getAmount(), $v->getAmount());
        $this->assertEquals($res, $v->getCurrency()->getCode());
    }

    public function gettersDataProvider(): array
    {
        return [
            [$this->mockCrypto('FOO'), $this->mockCrypto('BAR'), $this->mockMarketInfo(1, 2, 3), 'BAR'],
            [$this->mockCrypto('FOO'), $this->mockCrypto('BAZ'), $this->mockMarketInfo(2, 2, 6), 'BAZ'],
            [$this->mockCrypto('FOO'), $this->mockToken(), $this->mockMarketInfo(1, 2, 3), MoneyWrapper::TOK_SYMBOL],
            [$this->mockCrypto('FOO'), $this->mockToken(), $this->mockMarketInfo(6, 5, 1), MoneyWrapper::TOK_SYMBOL],
        ];
    }

    public function testSetGetQuote(): void
    {
        $quote = $this->mockCrypto('BAR');

        $ms = new MarketStatus(
            $this->mockCrypto('FOO'),
            $quote,
            $this->mockMarketInfo(1, 2, 3)
        );

        $this->assertEquals($quote, $ms->getQuote());

        $quote = $this->mockToken();

        $ms->setQuote($quote);

        $this->assertEquals($quote, $ms->getQuote());
    }

    public function testUpdateStats(): void
    {
        $ms = new MarketStatus(
            $this->mockCrypto('FOO'),
            $this->mockToken(),
            $this->mockMarketInfo(1, 2, 3)
        );

        $this->assertEquals([1, 2, 3], [
            $ms->getOpenPrice()->getAmount(),
            $ms->getLastPrice()->getAmount(),
            $ms->getDayVolume()->getAmount(),
        ]);

        $ms->updateStats($this->mockMarketInfo(3, 4, 5));

        $this->assertEquals([3, 4, 5], [
            $ms->getOpenPrice()->getAmount(),
            $ms->getLastPrice()->getAmount(),
            $ms->getDayVolume()->getAmount(),
        ]);
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockMarketInfo(int $o, int $l, int $v): MarketInfo
    {
        $mi = $this->createMock(MarketInfo::class);

        $mi->method('getOpen')->willReturn($this->mockMoney($o));
        $mi->method('getLast')->willReturn($this->mockMoney($l));
        $mi->method('getVolume')->willReturn($this->mockMoney($v));
        $mi->method('getDeal')->willReturn($this->mockMoney($v));

        return $mi;
    }

    private function mockMoney(int $amount): Money
    {
        return new Money($amount, new Currency('WEB'));
    }
}
