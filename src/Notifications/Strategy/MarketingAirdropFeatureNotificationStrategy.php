<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;

class MarketingAirdropFeatureNotificationStrategy implements NotificationStrategyInterface
{
    private UserNotificationManagerInterface $userNotificationManager;
    private MailerInterface $mailer;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
    }

    public function sendNotification(User $user): void
    {
        $token = $user->getTokens()[0] ?? null;

        if (!$token) {
            return;
        }

        $this->mailer->sendAirdropFeatureMail($token);
    }
}
