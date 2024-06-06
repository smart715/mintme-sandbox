<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Token\Token;
use App\Notifications\Strategy\RewardVolunteerAcceptedNotificationStrategy;

class RewardVolunteerAcceptedNotificationStrategyTest extends RewardNotificationStrategy
{
    public function testSendNotification(): void
    {
        $notificationStrategy = new RewardVolunteerAcceptedNotificationStrategy(
            $this->mockReward(),
            $this->token,
            $this->mockUserNotificationManager([$this->user, self::TYPE, $this->jsonData]),
            $this->mockMailer($this->user, 'sendRewardVolunteerAcceptedMail', [
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
