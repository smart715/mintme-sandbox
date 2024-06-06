<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Rewards\Reward;
use App\Mailer\MailerInterface;
use App\Notifications\Strategy\RewardNewNotificationStrategy;
use App\Repository\RewardRepository;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;

class RewardNewNotificationStrategyTest extends RewardNotificationStrategy
{

    private function mockMailerSent(InvokedCount $invokedCount): MailerInterface
    {
        $mail = $this->createMock(MailerInterface::class);
        $mail->expects($invokedCount)->method('sendRewardNewMail');

        return $mail;
    }

    public function testSendNotificationWithOneReward(): void
    {
        $rewardRepositoryMock = $this->createMock(RewardRepository::class);
        $rewardRepositoryMock->method('getRewardsByCreatedAtDayAndToken')
            ->willReturn([$this->createMock(Reward::class)]);

        $notificationStrategy = new RewardNewNotificationStrategy(
            $this->mockReward($this->token),
            $this->mockUserNotificationManager([$this->user, self::TYPE, $this->jsonData]),
            $this->mockMailerSent($this->once()),
            self::TYPE,
            $rewardRepositoryMock
        );

        $notificationStrategy->sendNotification($this->user);
    }

    public function testSendNotificationWithManyRewards(): void
    {
        $rewardRepositoryMock = $this->createMock(RewardRepository::class);
        $rewardRepositoryMock->method('getRewardsByCreatedAtDayAndToken')->willReturn([
            $this->createMock(Reward::class),
            $this->createMock(Reward::class),
            $this->createMock(Reward::class),
        ]);

        $notificationStrategy = new RewardNewNotificationStrategy(
            $this->mockReward($this->token),
            $this->mockUserNotificationManager([$this->user, self::TYPE, $this->jsonData]),
            $this->mockMailerSent($this->never()),
            self::TYPE,
            $rewardRepositoryMock
        );

        $notificationStrategy->sendNotification($this->user);
    }
}
