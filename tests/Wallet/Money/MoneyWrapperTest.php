<?php declare(strict_types = 1);

namespace App\Tests\Wallet\Money;

use App\Manager\CryptoManager;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Exchange\FixedExchange;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyWrapperTest extends TestCase
{
    public function testConvertToDecimalIfNotation(): void
    {
        $moneyWrapper = new MoneyWrapper($this->createMock(CryptoManager::class));

        $this->assertEquals('1000000000000', $moneyWrapper->parse('1', 'TOK')->getAmount());
        $this->assertEquals('100000', $moneyWrapper->parse('1e-7', 'TOK')->getAmount());
        $this->assertEquals('2200000', $moneyWrapper->parse('22e-7', 'TOK')->getAmount());
        $this->assertEquals('-2200000', $moneyWrapper->parse('-22e-7', 'TOK')->getAmount());
        $this->assertEquals('10000000000000000000', $moneyWrapper->parse('1e7', 'TOK')->getAmount());
        $this->assertEquals('220000000000000000000', $moneyWrapper->parse('22e7', 'TOK')->getAmount());
        $this->assertEquals('-220000000000000000000', $moneyWrapper->parse('-22e7', 'TOK')->getAmount());
        $this->assertEquals('22000000000000000000', $moneyWrapper->parse('2.2e7', 'TOK')->getAmount());
        $this->assertEquals('1000000000000000000000000', $moneyWrapper->parse('1e12', 'TOK')->getAmount());
        $this->assertEquals('1', $moneyWrapper->parse('1e-12', 'TOK')->getAmount());
        $this->assertEquals('1000000000000', $moneyWrapper->parse('001', 'TOK')->getAmount());
    }

    public function testConvertWithoutExchange(): void
    {
        $moneyWrapper = new MoneyWrapper($this->createMock(CryptoManager::class));
        $this->expectException(\Throwable::class);
        $moneyWrapper->convert(new Money(1, new Currency('WEB')), new Currency('BTC'));
    }

    public function testConvertSecondCallWithoutExchange(): void
    {
        $money = new Money(1000000000000000000, new Currency('WEB'));
        $currency = new Currency('BTC');
        $moneyWrapper = new MoneyWrapper($this->mockCryptoManager());
        $moneyWrapper->convert($money, $currency, new FixedExchange(['WEB' => ['BTC' => 1]]));
        $moneyWrapper->convert($money, $currency);

        $this->assertTrue(true);
    }

    public function testConvertByRatio(): void
    {
        $moneyWrapper = new MoneyWrapper($this->mockCryptoManager());

        $tokenAmount = $moneyWrapper->parse('1000', Symbols::TOK);

        $worth = $moneyWrapper->convertByRatio($tokenAmount, Symbols::BTC, '0.1');
        $expectedWorth = $moneyWrapper->parse('100', Symbols::BTC);

        $this->assertTrue($worth->equals($expectedWorth));
    }

    private function mockCryptoManager(): CryptoManager
    {
        $cryptos = [
            ['symbol' => 'BTC', 'subunit' => 8],
            ['symbol' => 'WEB', 'subunit' => 18],
        ];

        $cm = $this->createMock(CryptoManager::class);
        $cm->method('findSymbolAndSubunitArr')->willReturn($cryptos);

        return $cm;
    }
}
