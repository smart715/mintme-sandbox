<?php declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Exchange\MarketInfo;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MarketStatusTest extends TestCase
{
    /** @dataProvider gettersDataProvider */
    public function testGetters(Crypto $base, TradableInterface $quote, MarketInfo $mi, string $res): void
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
        $bd = $ms->getBuyDepth();
        $som = $ms->getSoldOnMarket();

        $this->assertEquals($mi->getOpen()->getAmount(), $o->getAmount());
        $this->assertEquals($res, $o->getCurrency()->getCode());

        $this->assertEquals($mi->getLast()->getAmount(), $l->getAmount());
        $this->assertEquals($res, $l->getCurrency()->getCode());

        $this->assertEquals($mi->getDeal()->getAmount(), $v->getAmount());
        $this->assertEquals($res, $v->getCurrency()->getCode());

        $this->assertEquals($mi->getMonthDeal()->getAmount(), $w->getAmount());
        $this->assertEquals($res, $w->getCurrency()->getCode());

        $this->assertEquals($mi->getBuyDepth()->getAmount(), $bd->getAmount());
        $this->assertEquals($res, $bd->getCurrency()->getCode());

        $this->assertEquals($mi->getSoldOnMarket()->getAmount(), $som->getAmount());
        $this->assertEquals(
            $quote instanceof Crypto ? $quote->getSymbol() : Symbols::TOK,
            $som->getCurrency()->getCode()
        );
    }

    public function gettersDataProvider(): array
    {
        return [
            [$this->mockCrypto('FOO'), $this->mockCrypto('BAR'), $this->mockMarketInfo(1, 2, 3, 4, 5, 8), 'FOO'],
            [$this->mockCrypto('FOO'), $this->mockCrypto('BAZ'), $this->mockMarketInfo(2, 2, 6, 6, 7, 8), 'FOO'],
            [$this->mockCrypto('FOO'), $this->mockToken(), $this->mockMarketInfo(1, 2, 3, 4, 5, 9), 'FOO'],
            [$this->mockCrypto('FOO'), $this->mockToken(), $this->mockMarketInfo(6, 5, 1, 2, 3, 8), 'FOO'],
        ];
    }

    public function testSetGetQuote(): void
    {
        $quote = $this->mockCrypto('BAR');

        $ms = new MarketStatus(
            $this->mockCrypto('FOO'),
            $quote,
            $this->mockMarketInfo(1, 2, 3, 4, 5, 8)
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
            $this->mockMarketInfo(1, 2, 3, 4, 5, 9)
        );

        $this->assertEquals([1, 2, 3, 4, 5, 9], [
            $ms->getOpenPrice()->getAmount(),
            $ms->getLastPrice()->getAmount(),
            $ms->getDayVolume()->getAmount(),
            $ms->getMonthVolume()->getAmount(),
            $ms->getBuyDepth()->getAmount(),
            $ms->getSoldOnMarket()->getAmount(),
        ]);

        $ms->updateStats($this->mockMarketInfo(3, 4, 5, 6, 7, 8));

        $this->assertEquals([3, 4, 5, 6, 7, 8], [
            $ms->getOpenPrice()->getAmount(),
            $ms->getLastPrice()->getAmount(),
            $ms->getDayVolume()->getAmount(),
            $ms->getMonthVolume()->getAmount(),
            $ms->getBuyDepth()->getAmount(),
            $ms->getSoldOnMarket()->getAmount(),
        ]);
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);

        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockToken(string $symbol = 'TEST'): Token
    {
        $token =  $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn($symbol);

        return $token;
    }

    private function mockMarketInfo(
        int $open = 0,
        int $last = 0,
        int $deal = 0,
        int $monthDeal = 0,
        int $buyDepth = 0,
        int $soldOnMarket = 0,
        int $low = 0,
        int $high = 0,
        int $close = 0,
        int $volume = 0,
        int $volumeDonation = 0
    ): MarketInfo {
        $marketInfo = $this->createMock(MarketInfo::class);

        $marketInfo->method('getOpen')->willReturn($this->mockMoney($open));
        $marketInfo->method('getLast')->willReturn($this->mockMoney($last));
        $marketInfo->method('getDeal')->willReturn($this->mockMoney($deal));
        $marketInfo->method('getMonthDeal')->willReturn($this->mockMoney($monthDeal));
        $marketInfo->method('getBuyDepth')->willReturn($this->mockMoney($buyDepth));
        $marketInfo->method('getSoldOnMarket')->willReturn($this->mockMoney($soldOnMarket));
        $marketInfo->method('getLow')->willReturn($this->mockMoney($low));
        $marketInfo->method('getHigh')->willReturn($this->mockMoney($high));
        $marketInfo->method('getClose')->willReturn($this->mockMoney($close));
        $marketInfo->method('getVolume')->willReturn($this->mockMoney($volume));
        $marketInfo->method('getVolumeDonation')->willReturn($this->mockMoney($volumeDonation));

        return $marketInfo;
    }

    private function mockMoney(int $amount): Money
    {
        return new Money($amount, new Currency('WEB'));
    }
}
