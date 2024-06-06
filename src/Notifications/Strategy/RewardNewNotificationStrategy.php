<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Rewards\Reward;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Repository\RewardRepository;

class RewardNewNotificationStrategy implements NotificationStrategyInterface
{
    private Reward $reward;
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;
    private string $type;
    private RewardRepository $rewardRepository;

    public function __construct(
        Reward $reward,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        string $type,
        RewardRepository $rewardRepository
    ) {
        $this->reward = $reward;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->type = $type;
        $this->rewardRepository = $rewardRepository;
    }

    /**
     * @throws \JsonException
     */
    public function sendNotification(User $user): void
    {
        $rewardTitle = $this->reward->getTitle();
        $rewardToken = $this->reward->getToken();
        $rewardTokenName = $rewardToken->getName();
        $tokenAvatar = $rewardToken->getImage()->getUrl();
        $rewardType = $this->type;
        $slug = $this->reward->getSlug();
        $jsonData = (array)json_encode([
            'rewardTitle' => $rewardTitle,
            'rewardToken' => $rewardTokenName,
            'tokenAvatar' => $tokenAvatar,
            'slug' => $slug,
        ], JSON_THROW_ON_ERROR);
        $this->userNotificationManager->createNotification($user, $this->type, $jsonData);

        $rewards = $this->rewardRepository->getRewardsByCreatedAtDayAndToken(
            $rewardToken,
            $this->reward->getType(),
            new \DateTimeImmutable(),
        );

        if (1 === count($rewards)) {
            $this->mailer->sendRewardNewMail($user, $rewardTokenName, $rewardTitle, $rewardType, $slug);
        }
    }
}
