<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Rewards\RewardParticipant;
use App\Notifications\Strategy\RewardParticipantRefundNotificationStrategy;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;

class RewardParticipantRefundNotificationStrategyTest extends RewardNotificationStrategy
{
    public function testSendNotification(): void
    {
        $participant = (new RewardParticipant())
            ->setPrice(new Money('100', new Currency(Symbols::TOK)));

        $jsonData = (array)json_encode([
                'rewardTitle' => self::REWARD_TITLE,
                'rewardToken' => self::TOKEN_NAME,
                'rewardAmount' => '100 TOK',
                'slug' => self::REWARD_SLUG,
                'ownerNickname' => self::OWNER_NICKNAME,
        ]);
        
        $notificationStrategy = new RewardParticipantRefundNotificationStrategy(
            $this->mockReward(),
            $participant,
            $this->token,
            $this->mockMailer($this->user, 'sendRewardParticipantRefundMail', [
                $this->user,
                self::OWNER_NICKNAME,
                '100 TOK',
                self::TOKEN_NAME,
                self::REWARD_TITLE,
                self::REWARD_SLUG,
            ]),
            $this->mockMoneyWrapper(),
            $this->mockUserNotificationManager([$this->user, self::TYPE, $jsonData]),
            self::TYPE,
        );

        $notificationStrategy->sendNotification($this->user);
    }
}
