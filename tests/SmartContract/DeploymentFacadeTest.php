<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\DeployCostFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
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
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class DeploymentFacadeTest extends TestCase
{
    public function testExecute(): void
    {
        $process = new DeploymentFacade(
            $this->createMock(EntityManager::class),
            $this->mockCostFetcher('2000000000000000000'),
            $this->mockBalanceHandler($this->once(), '2000000000000000000'),
            $this->mockContractHandler($this->once())
        );

        $process->execute(
            $this->createMock(User::class),
            $this->mockToken($this->once(), $this->once()),
            $this->mockCrypto('WEB')
        );
    }

    public function testExecuteWithLowBalance(): void
    {
        $process = new DeploymentFacade(
            $this->createMock(EntityManager::class),
            $this->mockCostFetcher('2000000000000000000'),
            $this->mockBalanceHandler($this->never(), '1000000000000000000'),
            $this->mockContractHandler($this->never())
        );

          $this->expectException(BalanceException::class);

          $process->execute(
              $this->createMock(User::class),
              $this->mockToken($this->never(), $this->never()),
              $this->mockCrypto('WEB')
          );
    }

    private function mockContractHandler(Invocation $invocation): ContractHandlerInterface
    {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);
        $contractHandler->expects($invocation)->method('deploy');

        return $contractHandler;
    }

    private function mockBalanceHandler(Invocation $withdrawInvocation, string $balance): BalanceHandlerInterface
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getAvailable')->willReturn(
            new Money($balance, new Currency(Symbols::WEB))
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler->method('balance')->willReturn($balanceResult);
        $balanceHandler->expects($withdrawInvocation)->method('withdraw');

        return $balanceHandler;
    }

    private function mockCostFetcher(string $balance): DeployCostFetcherInterface
    {
        $costFetcher = $this->createMock(DeployCostFetcherInterface::class);
        $costFetcher->method('getDeployWebCost')
            ->willReturn(new Money($balance, new Currency(Symbols::WEB)));

        return $costFetcher;
    }

    private function mockToken(Invocation $pendingInvocation, Invocation $costInvocation): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getOwner')->willReturn($this->createMock(User::class));
        $token->expects($pendingInvocation)->method('setPendingDeployment');
        $token->expects($costInvocation)->method('setDeployCost');

        return $token;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }
}
