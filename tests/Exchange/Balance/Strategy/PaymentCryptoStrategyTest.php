<?php

namespace App\Tests\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\PaymentCryptoStrategy;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class PaymentCryptoStrategyTest extends TestCase
{
    public function testDeposit(): void
    {
        $strategy = new PaymentCryptoStrategy(
            $this->mockBalanceHandler($this->once()),
            $this->mockMoneyWrapper($this->once())
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->mockCrypto(),
            '100'
        );
    }

    private function mockBalanceHandler(Invocation $invocation): BalanceHandlerInterface
    {
        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($invocation)->method('deposit');

        return $handler;
    }

    private function mockMoneyWrapper(Invocation $parseInv): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);
        $wrapper->expects($parseInv)->method('parse')
            ->willReturn(new Money('1000000000000000000', new Currency('WEB')));

        return $wrapper;
    }

    public function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')
            ->willReturn(new Money('1000000000000000', new Currency('WEB')));

        return $crypto;
    }
}
