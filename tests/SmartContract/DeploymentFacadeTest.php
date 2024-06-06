<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\ConnectCostFetcherInterface;
use App\Communications\DeployCostFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Model\BalanceResult;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\DeploymentFacade;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManager;
use Money\Currency;
use Money\Money;
use phpDocumentor\Reflection\Types\This;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class DeploymentFacadeTest extends TestCase
{
    public function testExecute(): void
    {
        $process = new DeploymentFacade(
            $this->createMock(EntityManager::class),
            $this->mockCostFetcher('2000000000000000000'),
            $this->createMock(ConnectCostFetcherInterface::class),
            $this->mockBalanceHandler($this->once(), '2000000000000000000'),
            $this->mockContractHandler($this->once())
        );

        $process->execute(
            $this->createMock(User::class),
            $this->mockToken($this->once()),
            $this->mockCrypto('WEB')
        );
    }

    public function testExecuteWithLowBalance(): void
    {
        $process = new DeploymentFacade(
            $this->createMock(EntityManager::class),
            $this->mockCostFetcher('2000000000000000000'),
            $this->createMock(ConnectCostFetcherInterface::class),
            $this->mockBalanceHandler($this->never(), '1000000000000000000'),
            $this->mockContractHandler($this->never())
        );

          $this->expectException(BalanceException::class);

          $process->execute(
              $this->createMock(User::class),
              $this->mockToken($this->never()),
              $this->mockCrypto('WEB')
          );
    }

    private function mockContractHandler(InvokedCount $invocation): ContractHandlerInterface
    {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);
        $contractHandler->expects($invocation)->method('deploy');

        return $contractHandler;
    }

    private function mockBalanceHandler(InvokedCount $withdrawInvocation, string $balance): BalanceHandlerInterface
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getFullAvailable')->willReturn(
            new Money($balance, new Currency(Symbols::WEB))
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler->method('balance')->willReturn($balanceResult);
        $balanceHandler->expects($withdrawInvocation)->method('withdrawBonus');

        return $balanceHandler;
    }

    private function mockCostFetcher(string $balance): DeployCostFetcherInterface
    {
        $costFetcher = $this->createMock(DeployCostFetcherInterface::class);
        $costFetcher->method('getCost')
            ->willReturn(new Money($balance, new Currency(Symbols::WEB)));

        return $costFetcher;
    }

    private function mockToken(InvokedCount $invocation): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getOwner')->willReturn($this->createMock(User::class));
        $token->expects($invocation)->method('addDeploy');

        return $token;
    }

    /**
     * @return Crypto|MockObject
     */
    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }
}
