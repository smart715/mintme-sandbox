<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Mailer\MailerInterface;

class MarketingAirdropFeatureNotificationStrategy implements NotificationStrategyInterface
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
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
