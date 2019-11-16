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
        $w = $ms->getMonthVolume();

        $this->assertEquals($mi->getOpen()->getAmount(), $o->getAmount());
        $this->assertEquals($res, $o->getCurrency()->getCode());

        $this->assertEquals($mi->getLast()->getAmount(), $l->getAmount());
        $this->assertEquals($res, $l->getCurrency()->getCode());

        $this->assertEquals($mi->getDeal()->getAmount(), $v->getAmount());
        $this->assertEquals($res, $v->getCurrency()->getCode());

        $this->assertEquals($mi->getMonthDeal()->getAmount(), $w->getAmount());
        $this->assertEquals($res, $w->getCurrency()->getCode());
    }

    public function gettersDataProvider(): array
    {
        return [
            [$this->mockCrypto('FOO'), $this->mockCrypto('BAR'), $this->mockMarketInfo(1, 2, 3, 4), 'FOO'],
            [$this->mockCrypto('FOO'), $this->mockCrypto('BAZ'), $this->mockMarketInfo(2, 2, 6, 6), 'FOO'],
            [$this->mockCrypto('FOO'), $this->mockToken(), $this->mockMarketInfo(1, 2, 3, 4), 'FOO'],
            [$this->mockCrypto('FOO'), $this->mockToken(), $this->mockMarketInfo(6, 5, 1, 2), 'FOO'],
        ];
    }

    public function testSetGetQuote(): void
    {
        $quote = $this->mockCrypto('BAR');

        $ms = new MarketStatus(
            $this->mockCrypto('FOO'),
            $quote,
            $this->mockMarketInfo(1, 2, 3, 4)
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
            $this->mockMarketInfo(1, 2, 3, 4)
        );

        $this->assertEquals([1, 2, 3, 4], [
            $ms->getOpenPrice()->getAmount(),
            $ms->getLastPrice()->getAmount(),
            $ms->getDayVolume()->getAmount(),
            $ms->getMonthVolume()->getAmount(),
        ]);

        $ms->updateStats($this->mockMarketInfo(3, 4, 5, 6));

        $this->assertEquals([3, 4, 5, 6], [
            $ms->getOpenPrice()->getAmount(),
            $ms->getLastPrice()->getAmount(),
            $ms->getDayVolume()->getAmount(),
            $ms->getMonthVolume()->getAmount(),
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

    private function mockMarketInfo(int $o, int $l, int $d, int $wd): MarketInfo
    {
        $mi = $this->createMock(MarketInfo::class);

        $mi->method('getOpen')->willReturn($this->mockMoney($o));
        $mi->method('getLast')->willReturn($this->mockMoney($l));
        $mi->method('getDeal')->willReturn($this->mockMoney($d));
        $mi->method('getMonthDeal')->willReturn($this->mockMoney($wd));

        return $mi;
    }

    private function mockMoney(int $amount): Money
    {
        return new Money($amount, new Currency('WEB'));
    }
}
