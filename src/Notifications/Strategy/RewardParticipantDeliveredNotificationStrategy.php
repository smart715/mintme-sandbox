<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;

class RewardParticipantDeliveredNotificationStrategy implements NotificationStrategyInterface
{
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private string $type;
    private Token $token;
    private Reward $reward;

    public function __construct(
        Reward $reward,
        Token $token,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        string $type
    ) {
        $this->reward = $reward;
        $this->token = $token;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->type = $type;
    }

    /**
     * @throws \JsonException
     */
    public function sendNotification(User $user): void
    {
        $rewardTitle = $this->reward->getTitle();
        $rewardToken = $this->token;
        $rewardTokenName = $rewardToken->getName();
        $tokenAvatar = $rewardToken->getImage()->getUrl();
        $slug = $this->reward->getSlug();
        $jsonData = (array)json_encode([
            'rewardTitle' => $rewardTitle,
            'rewardToken' => $rewardTokenName,
            'tokenAvatar' => $tokenAvatar,
            'slug' => $slug,
        ], JSON_THROW_ON_ERROR);
        $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        $this->mailer->sendRewardParticipantDeliveredMail($user, $rewardTokenName, $rewardTitle, $slug);
    }
}
