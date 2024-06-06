<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class RewardParticipantRefundNotificationStrategy implements NotificationStrategyInterface
{
    private MailerInterface $mailer;
    private Token $token;
    private Reward $reward;
    private MoneyWrapperInterface $moneyWrapper;
    private RewardParticipant $rewardParticipant;
    private UserNotificationManagerInterface $userNotificationManager;
    private string $type;

    public function __construct(
        Reward $reward,
        RewardParticipant $rewardParticipant,
        Token $token,
        MailerInterface $mailer,
        MoneyWrapperInterface $moneyWrapper,
        UserNotificationManagerInterface $userNotificationManager,
        string $type
    ) {
        $this->reward = $reward;
        $this->token = $token;
        $this->mailer = $mailer;
        $this->moneyWrapper = $moneyWrapper;
        $this->rewardParticipant = $rewardParticipant;
        $this->userNotificationManager = $userNotificationManager;
        $this->type = $type;
    }

    public function sendNotification(User $user): void
    {
        $rewardTitle = $this->reward->getTitle();
        $rewardToken = $this->token->getName();
        $ownerNickname = $this->token->getOwner()->getNickname();
        $amount = $this->moneyWrapper->format($this->rewardParticipant->getFullPrice(), false);
        $slug = $this->reward->getSlug();
        $jsonData = (array)json_encode([
            'rewardTitle' => $rewardTitle,
            'rewardToken' => $rewardToken,
            'rewardAmount' => $amount,
            'slug' => $slug,
            'ownerNickname' => $ownerNickname,
        ], JSON_THROW_ON_ERROR);
        $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        $this->mailer->sendRewardParticipantRefundMail(
            $user,
            $ownerNickname,
            $amount,
            $rewardToken,
            $rewardTitle,
            $slug
        );
    }
}
