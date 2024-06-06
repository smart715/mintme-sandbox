<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\MoveTokenBalanceToBonusBalanceCommand;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\PostUserShareRewardRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Exception;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MoveTokenBalanceToBonusBalanceCommandTest extends KernelTestCase
{
    /**
     * @dataProvider executeDataProvider
     * @param string|int|null $tokenName
     */
    public function testExecute(
        ?string $email,
        $tokenName,
        ?User $user,
        ?Token $token,
        string $balance,
        bool $hasAirdropReward,
        bool $hasSharePostReward,
        ?bool $force,
        bool $success,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new MoveTokenBalanceToBonusBalanceCommand(
                $this->mockTokenManager($token),
                $this->mockUserManager($user),
                $this->mockBalanceHandler($balance, $success),
                $this->mockMoneyWrapper(),
                $this->mockAirdropParticipantRepository($hasAirdropReward),
                $this->mockPostUserShareRewardRepository($hasSharePostReward)
            )
        );

        $command = $application->find('app:move-token-balance-to-bonus');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => $email,
            'token' => $tokenName,
            '--force' => $force,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            "Email is not a string format will return an error and status code equals 1" => [
                "email" => null,
                "tokenName" => "TEST",
                "user" => $this->mockUser(),
                "token" => $this->mockToken(),
                "balance" => "1",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => false,
                "expected" => "Wrong email argument, it must be a string!",
                "statusCode" => 1,
            ],
            "User does not exist will return an error and status code equals 1" => [
                "email" => "FOO",
                "tokenName" => "TEST",
                "user" => null,
                "token" => $this->mockToken(),
                "balance" => "1",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => false,
                "expected" => "User with email 'FOO' not found!",
                "statusCode" => 1,
            ],
            "Token name is not a string format will return an error and status code equals 1" => [
                "email" => "FOO",
                "tokenName" => 1111,
                "user" => $this->mockUser(),
                "token" =>  $this->mockToken(),
                "balance" => "1",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => false,
                "expected" => "Wrong token name argument, it must be a string!",
                "statusCode" => 1,
            ],
            "Token does not exist will return an error and status code equals 1" => [
                "email" => "FOO",
                "tokenName" => "TEST",
                "user" => $this->mockUser(),
                "token" =>  null,
                "balance" => "1",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => false,
                "expected" => "Token 'TEST' not found!",
                "statusCode" => 1,
            ],
            "Email is set and token name is not set will return a success and status code equals 0" => [
                "email" => "FOO",
                "tokenName" => null,
                "user" => $this->mockUser([$this->mockToken()]),
                "token" => null,
                "balance" => "1",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => true,
                "expected" => "Successfully moved balance of 1 of 1 tokens of user FOO",
                "statusCode" => 0,
            ],
            "Email is set and token name is set will return a success and status code equals 0" => [
                "email" => "FOO",
                "tokenName" => "TEST",
                "user" => $this->mockUser(),
                "token" =>  $this->mockToken(),
                "balance" => "1",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => true,
                "expected" => "'TEST' :: balance was successfully moved to bonus balance.",
                "statusCode" => 0,
            ],
            "Balance is zero will return a warning and status code equals 0" => [
                "email" => "FOO",
                "tokenName" => "TEST",
                "user" => $this->mockUser(),
                "token" => $this->mockToken(),
                "balance" => "0",
                "hasAirdropReward" => true,
                "hasSharePostReward" => true,
                "force" => null,
                "success" => false,
                "expected" => "Token 'TEST' :: Balance is 0. Nothing will be moved",
                "statusCode" => 0,
            ],
            "User doesn't have tokens earned from airdrops or share post rewards will return a warning and status code equals 0" => [
                "email" => "FOO",
                "tokenName" => "TEST",
                "user" => $this->mockUser(),
                "token" => $this->mockToken(),
                "balance" => "1",
                "hasAirdropReward" => false,
                "hasSharePostReward" => false,
                "force" => false,
                "success" => false,
                "expected" => "Token 'TEST' :: User doesn't have tokens earned",
                "statusCode" => 0,
            ],
        ];
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->method('exchangeBalance')
            ->willReturn($this->dummyMoneyObject('1'));
        $balanceHandler
            ->method('depositBonus')
            ->willThrowException(new Exception());
        $balanceHandler
            ->expects($this->once())
            ->method('rollback');

        $application->add(
            new MoveTokenBalanceToBonusBalanceCommand(
                $this->mockTokenManager($this->mockToken()),
                $this->mockUserManager($this->mockUser()),
                $balanceHandler,
                $this->mockMoneyWrapper(),
                $this->mockAirdropParticipantRepository(true),
                $this->mockPostUserShareRewardRepository(true)
            )
        );

        $command = $application->find('app:move-token-balance-to-bonus');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email' => 'FOO',
            'token' => 'TEST',
            '--force' => null,
        ]);

        $this->assertStringContainsString('Something went wrong:', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('findByName')
            ->willReturn($token);

        return $tokenManager;
    }

    private function mockUserManager(?User $user): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager
            ->method('findUserByEmail')
            ->willReturn($user);

        return $userManager;
    }

    private function mockBalanceHandler(string $balance, bool $success): BalanceHandlerInterface
    {
        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->method('exchangeBalance')
            ->willReturn($this->dummyMoneyObject($balance));
        $balanceHandler
            ->expects($success ? $this->once() : $this->never())
            ->method('beginTransaction');
        $balanceHandler
            ->expects($success ? $this->once() : $this->never())
            ->method('withdraw');
        $balanceHandler
            ->expects($success ? $this->once() : $this->never())
            ->method('depositBonus');

        return $balanceHandler;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method('parse')
            ->willReturn($this->dummyMoneyObject());
        $moneyWrapper
            ->method('convert')
            ->willReturn($this->dummyMoneyObject());
        $moneyWrapper
            ->method('format')
            ->willReturnCallback(function (Money $money): string {
                return $money->getAmount();
            });

        return $moneyWrapper;
    }

    private function mockAirdropParticipantRepository(bool $hasAirdropReward): AirdropParticipantRepository
    {
        $airdropParticipantRepository = $this->createMock(AirdropParticipantRepository::class);
        $airdropParticipantRepository
            ->method('hasAirdropReward')
            ->willReturn($hasAirdropReward);

        return $airdropParticipantRepository;
    }

    private function mockPostUserShareRewardRepository(bool $hasSharePostReward): PostUserShareRewardRepository
    {
        $postUserShareRewardRepository = $this->createMock(PostUserShareRewardRepository::class);
        $postUserShareRewardRepository
            ->method('hasSharePostReward')
            ->willReturn($hasSharePostReward);

        return $postUserShareRewardRepository;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->method('getId')
            ->willReturn(1);
        $token
            ->method('getName')
            ->willReturn('TEST');

        return $token;
    }

    private function mockUser(array $tokens = []): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getId')
            ->willReturn(1);
        $user
            ->method('getTokens')
            ->willReturn($tokens);

        return $user;
    }

    private function dummyMoneyObject(string $amount = '1', string $symbol = 'TOK'): Money
    {
        return new Money($amount, new Currency($symbol));
    }
}
