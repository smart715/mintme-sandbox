<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\DepositCryptoStrategy;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class DepositCryptoStrategyTest extends TestCase
{
    public function testDeposit(): void
    {
        $strategy = new DepositCryptoStrategy(
            $this->mockBalanceHandler($this->once()),
            $this->mockMoneyWrapper($this->once())
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->createMock(Crypto::class),
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
}
