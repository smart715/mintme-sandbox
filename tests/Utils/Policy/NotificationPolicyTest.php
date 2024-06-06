<?php declare(strict_types = 1);

namespace App\Tests\Utils\Policy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\TokenManagerInterface;
use App\Utils\Policy\NotificationPolicy;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NotificationPolicyTest extends TestCase
{
    /**
     * @dataProvider  notificationPolicyProvider
     */
    public function testNotificationPolicy(int $userBalance, int $minTokensAmount, bool $expected): void
    {
        $policy = new NotificationPolicy(
            $this->mockTokenManager($userBalance),
            $this->mockContainer(),
            $this->mockBalanceHandler(),
            $this->mockMoneyWrapper($minTokensAmount)
        );

        $result = $policy->canReceiveNotification(
            $this->mockUser(),
            $this->mockToken()
        );

        $this->assertEquals($expected, $result);
    }


    public function notificationPolicyProvider(): array
    {
        return [
            "Return true if user has enough tokens" => [
                "userBalance" => 100,
                "minTokensAmount" => 10,
                "expected" => true,
            ],
            "Return true if user equal token to the minimum" => [
                "userBalance" => 10,
                "minTokensAmount" => 10,
                "expected" => true,
            ],
            "Return false if user has not enough tokens" => [
                "userBalance" => 10,
                "minTokensAmount" => 100,
                "expected" => false,
            ],
        ];
    }

    private function mockTokenManager(int $userBalance): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->expects($this->once())
            ->method('getRealBalance')
            ->willReturn($this->mockBalanceResult($userBalance));

        return $tokenManager;
    }

    private function mockContainer(): ContainerInterface
    {
        return $this->createMock(ContainerInterface::class);
    }

    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    private function mockMoneyWrapper(int $minTokensAmount): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);

        $moneyWrapper->expects($this->once())
            ->method('parse')
            ->willReturn($this->mockMoney($minTokensAmount));

        return $moneyWrapper;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }


    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockMoney(int $balance = 0): Money
    {
        return new Money($balance, new Currency('TOK'));
    }

    private function mockBalanceResult(int $userBalance): BalanceResult
    {
        $balanceResult = $this->createMock(BalanceResult::class);

        $balanceResult->expects($this->once())
            ->method('getAvailable')
            ->willReturn($this->mockMoney($userBalance));

        return $balanceResult;
    }
}
