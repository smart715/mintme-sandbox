<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\DepositTokenStrategy;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class DepositTokenStrategyTest extends TestCase
{
    public function testDeposit(): void
    {
        $strategy = new DepositTokenStrategy(
            $this->mockBalanceHandler($this->once(), $this->once()),
            $this->mockWallet(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager()
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->createMock(Token::class),
            '100'
        );
    }

    private function mockBalanceHandler(Invocation $depositInv, Invocation $withdrawInv): BalanceHandlerInterface
    {
        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($depositInv)->method('deposit');
        $handler->expects($withdrawInv)->method('withdraw');

        return $handler;
    }

    private function mockWallet(): WalletInterface
    {
        $wallet = $this->createMock(WalletInterface::class);
        $wallet->method('getFee')
            ->willReturn(new Money('1000000000000000', new Currency('WEB')));

        return $wallet;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method("parse")->wilLReturn(new Money("100000000000000", new Currency('TOK')));

        return $mw;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);
        $manager->method('findBySymbol')->willReturn((new Crypto()));

        return $manager;
    }
}
