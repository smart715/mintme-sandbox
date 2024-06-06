<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\SendEmailGroupedBountiesRewardsCommand;
use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\RewardRepository;
use App\Utils\Policy\NotificationPolicyInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendEmailGroupedBountiesRewardsCommandTest extends KernelTestCase
{
    /**
     * @dataProvider executeDataProvider
     * @param string|int $type
     * @param string|int|null $date
     */
    public function testExecute(
        $type,
        $date,
        int $sendCount,
        int $invokeCount,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $users = [
            $this->mockUser(1),
            $this->mockUser(2),
            $this->mockUser(3),
        ];

        $token = $this->mockToken('TEST', $this->mockUser(99999), $users);

        $rewards = [
            $this->mockReward($token),
            $this->mockReward($token),
            $this->mockReward($token),
        ];

        $application->add(
            new SendEmailGroupedBountiesRewardsCommand(
                $this->mockMailer($rewards[0]->getToken()->getName(), $sendCount),
                $this->mockRewardRepository($rewards, $invokeCount),
                $this->mockTokenManager($rewards[0]->getToken()),
                $this->mockNotificationPolicy(),
            )
        );

        $command = $application->find('app:send-grouped-rewards-bounties');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'type' => $type,
            'date' => $date,
        ]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return [
            "Date is not a string format will return an error and status code equals 1" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_ALL,
                "date" => 23-01-01,
                "sendCount" => 0,
                "invokeCount" => 0,
                "expected" => "Wrong date argument",
                "statusCode" => 1,
            ],
            "Date is not valid will return an error and status code equals 1" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_ALL,
                "date" => "23-01-01",
                "sendCount" => 0,
                "invokeCount" => 0,
                "expected" => "23-01-01: is not a valid date",
                "statusCode" => 1,
            ],
            "Type is not a string format will return an error and status code equals 1" => [
                "type" => 1,
                "date" => null,
                "sendCount" => 0,
                "invokeCount" => 0,
                "expected" => "Wrong type argument",
                "statusCode" => 1,
            ],
            "Type is not all, bounty or reward will return an error and status code equals 1" => [
                "type" => "invalid-type",
                "date" => null,
                "sendCount" => 0,
                "invokeCount" => 0,
                "expected" => "type is wrong, can be all, bounty or reward",
                "statusCode" => 1,
            ],
            "Type is 'all' will return a success and status code equals 0" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_ALL,
                "date" => null,
                "sendCount" => 6,
                "invokeCount" => 2,
                "expected" => "Emails has been sent",
                "statusCode" => 0,
            ],
            "Type is 'all' and date is set will return a success and status code equals 0" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_ALL,
                "date" => "2023-01-01",
                "sendCount" => 6,
                "invokeCount" => 2,
                "expected" => "Emails has been sent",
                "statusCode" => 0,
            ],
            "Type is 'bounty' will return a success and status code equals 0" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_BOUNTY,
                "date" => null,
                "sendCount" => 3,
                "invokeCount" => 1,
                "expected" => "Emails has been sent",
                "statusCode" => 0,
            ],
            "Type is 'bounty' and date is set will a return success and status code equals 0" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_BOUNTY,
                "date" => "2023-01-01",
                "sendCount" => 3,
                "invokeCount" => 1,
                "expected" => "Emails has been sent",
                "statusCode" => 0,
            ],
            "Type is 'reward' will return a success and status code equals 0" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_REWARD,
                "date" => null,
                "sendCount" => 3,
                "invokeCount" => 1,
                "expected" => "Emails has been sent",
                "statusCode" => 0,
            ],
            "Type is 'reward' and date is set will return a success and status code equals 0" => [
                "type" => SendEmailGroupedBountiesRewardsCommand::TYPE_REWARD,
                "date" => "2023-01-01",
                "sendCount"  => 3,
                "invokeCount" => 1,
                "expected" => "Emails has been sent",
                "statusCode" => 0,
            ],
        ];
    }

    private function mockMailer(string $tokenName, int $sendCount): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->exactly($sendCount))
            ->method('sendGroupedRewardsMail')
            ->with($this->anything(), $tokenName, $this->anything(), $this->anything());

        return $mailer;
    }

    private function mockRewardRepository(array $rewards, int $invokeCount): RewardRepository
    {
        $rewardRepository = $this->createMock(RewardRepository::class);
        $rewardRepository
            ->expects($this->exactly($invokeCount))
            ->method('getRewardByCreatedAtDay')
            ->willReturn($rewards);

        return $rewardRepository;
    }

    private function mockTokenManager(Token $token): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('findByName')
            ->willReturn($token);

        return $tokenManager;
    }

    private function mockNotificationPolicy(): NotificationPolicyInterface
    {
        $notificationPolicy = $this->createMock(NotificationPolicyInterface::class);
        $notificationPolicy
            ->method('canReceiveNotification')
            ->willReturn(true);

        return $notificationPolicy;
    }

    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getId')
            ->willReturn($id);

        return $user;
    }

    private function mockToken(string $name, User $owner, array $users): Token
    {
        $token= $this->createMock(Token::class);
        $token
            ->method('getOwner')
            ->willReturn($owner);
        $token
            ->method('getName')
            ->willReturn($name);
        $token
            ->method('getUsers')
            ->willReturn($users);

        return $token;
    }

    private function mockReward(Token $token): Reward
    {
        $reward = $this->createMock(Reward::class);
        $reward
            ->method('getToken')
            ->willReturn($token);

        return $reward;
    }
}
