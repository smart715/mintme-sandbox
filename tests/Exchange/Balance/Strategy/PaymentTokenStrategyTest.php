<?php

namespace App\Tests\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\PaymentTokenStrategy;
use App\Manager\CryptoManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class PaymentTokenStrategyTest extends TestCase
{
    public function testDeposit(): void
    {
        $strategy = new PaymentTokenStrategy(
            $this->mockBalanceHandler($this->exactly(2)),
            $this->mockCryptoManager()
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->createMock(Token::class),
            '100'
        );
    }

    private function mockBalanceHandler(Invocation $invocation): BalanceHandlerInterface
    {
        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($invocation)->method('deposit');

        return $handler;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')
            ->willReturn(new Money('1000000000000000', new Currency('WEB')));

        $manager = $this->createMock(CryptoManagerInterface::class);
        $manager->method('findBySymbol')->willReturn($crypto);

        return $manager;
    }
}
