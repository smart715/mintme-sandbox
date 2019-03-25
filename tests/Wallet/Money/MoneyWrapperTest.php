<?php declare(strict_types = 1);

namespace App\Tests\Wallet\Money;

use App\Manager\CryptoManager;
use App\Wallet\Money\MoneyWrapper;
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
    }
}
