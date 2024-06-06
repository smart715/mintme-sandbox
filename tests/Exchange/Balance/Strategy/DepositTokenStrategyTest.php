<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\User;
use App\Exception\InvalidTokenDeploy;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Strategy\DepositTokenStrategy;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class DepositTokenStrategyTest extends TestCase
{
    public function testDeposit(): void
    {
        $strategy = new DepositTokenStrategy(
            $this->mockBalanceHandler($this->once(), $this->once()),
            $this->mockWallet(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->createMock(Crypto::class)
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->mockToken(),
            '100'
        );
    }

    public function testDepositWithNonDeployedToken(): void
    {
        $strategy = new DepositTokenStrategy(
            $this->mockBalanceHandler($this->never(), $this->never()),
            $this->mockWallet(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->createMock(Crypto::class)
        );
        $this->expectException(InvalidTokenDeploy::class);

        $strategy->deposit(
            $this->createMock(User::class),
            $this->mockToken(false),
            '100'
        );
    }

    public function testWillNotProceedWithdrawBaseFeeIfNoDepositInfo(): void
    {
        $strategy = new DepositTokenStrategy(
            $this->mockBalanceHandler($this->once(), $this->never()),
            $this->mockWallet(false),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->createMock(Crypto::class)
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->mockToken(),
            '100'
        );
    }

    public function testWillNotProceedWithdrawBaseFeeIfNegativeFee(): void
    {
        $strategy = new DepositTokenStrategy(
            $this->mockBalanceHandler($this->once(), $this->never()),
            $this->mockWallet(false, '-1'),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->createMock(Crypto::class)
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->mockToken(),
            '100',
        );
    }

    public function testWillNotProceedWithdrawBaseFeeIfZeroFee(): void
    {
        $strategy = new DepositTokenStrategy(
            $this->mockBalanceHandler($this->once(), $this->never()),
            $this->mockWallet(false, '0'),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->createMock(Crypto::class)
        );

        $strategy->deposit(
            $this->createMock(User::class),
            $this->mockToken(),
            '100',
        );
    }

    private function mockBalanceHandler(InvokedCount $depositInv, InvokedCount $withdrawInv): BalanceHandlerInterface
    {
        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($depositInv)->method('deposit');
        $handler->expects($withdrawInv)->method('withdraw');

        return $handler;
    }

    private function mockWallet(bool $depositExist = true, string $fee = '100'): WalletInterface
    {
        $wallet = $this->createMock(WalletInterface::class);
        $depositInfo = $this->createMock(DepositInfo::class);
        $depositInfo->method('getFee')
            ->willReturn(new Money($fee, new Currency('WEB')));

        $wallet->method('getDepositInfo')
            ->willReturn($depositExist ? $depositInfo : null);

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
        $manager->method('findBySymbol')->willReturn($this->createMock(Crypto::class));

        return $manager;
    }

    private function mockToken(bool $isDeployed = true): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())
            ->method('getDeployByCrypto')
            ->willReturn($isDeployed ? $this->createMock(TokenDeploy::class) : null);

        return $token;
    }
}
