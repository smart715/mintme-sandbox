<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\DeployCostFetcherInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\DeploymentProcess;
use Doctrine\ORM\EntityManager;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;

class DeploymentProcessTest extends TestCase
{
    public function testExecute(): void
    {
        $process = new DeploymentProcess(
            $this->createMock(EntityManager::class),
            $this->mockCostFetcher('2000000000000000000'),
            $this->mockBalanceHandler($this->once(), '2000000000000000000'),
            $this->mockContractHandler($this->once())
        );

        $process->execute(
            $this->createMock(User::class),
            $this->mockToken($this->once())
        );
    }

    public function testExecuteWithLowBalance(): void
    {
        $process = new DeploymentProcess(
            $this->createMock(EntityManager::class),
            $this->mockCostFetcher('1000000000000000000'),
            $this->mockBalanceHandler($this->never(), '2000000000000000000'),
            $this->mockContractHandler($this->never())
        );

        $this->expectException(ApiBadRequestException::class);

        $process->execute(
            $this->createMock(User::class),
            $this->mockToken($this->never())
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
            new Money($balance, new Currency(Token::WEB_SYMBOL))
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
            ->willReturn(new Money($balance, new Currency(Token::WEB_SYMBOL)));

        return $costFetcher;
    }

    private function mockToken(Invocation $invocation): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($invocation)->method('setPendingDeployment');
        $token->expects($invocation)->method('setDeployCost');

        return $token;
    }
}
