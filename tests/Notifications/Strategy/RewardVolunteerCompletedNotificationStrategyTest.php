<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Notifications\Strategy\RewardVolunteerCompletedNotificationStrategy;

class RewardVolunteerCompletedNotificationStrategyTest extends RewardNotificationStrategy
{
    public function testSendNotification(): void
    {
        $notificationStrategy = new RewardVolunteerCompletedNotificationStrategy(
            $this->mockReward(),
            $this->token,
            $this->mockUserNotificationManager([$this->user, self::TYPE, $this->jsonData]),
            $this->mockMailer($this->user, 'sendRewardVolunteerCompletedMail', [
                $this->user,
                self::TOKEN_NAME,
                self::REWARD_TITLE,
                self::REWARD_SLUG,
            ]),
            self::TYPE,
        );

        $notificationStrategy->sendNotification($this->user);
    }
}
