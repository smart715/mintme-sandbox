<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Rewards\Reward;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;

class RewardParticipantNotificationStrategy implements NotificationStrategyInterface
{
    private Reward $reward;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private string $type;

    public function __construct(
        Reward $reward,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        string $type
    ) {
        $this->reward = $reward;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->type = $type;
    }

    /**
     * @throws \JsonException
     */
    public function sendNotification(User $user): void
    {
        $token = $this->reward->getToken();
        $rewardToken = $token->getName();
        $tokenAvatar = $token->getImage()->getUrl();
        $rewardTitle = $this->reward->getTitle();
        $slug = $this->reward->getSlug();
        $jsonData = (array)json_encode([
            'rewardTitle' => $rewardTitle,
            'rewardToken' => $rewardToken,
            'tokenAvatar' => $tokenAvatar,
            'slug' => $slug,
        ], JSON_THROW_ON_ERROR);
        $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        $this->mailer->sendRewardNewParticipantMail($user, $rewardToken, $rewardTitle, $slug);
    }
}
