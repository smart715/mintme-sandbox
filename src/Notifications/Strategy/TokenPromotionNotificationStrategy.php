<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;

class TokenPromotionNotificationStrategy implements NotificationStrategyInterface
{
    private MailerInterface $mailer;
    private Token $token;

    public function __construct(
        MailerInterface $mailer,
        Token $token
    ) {
        $this->mailer = $mailer;
        $this->token = $token;
    }

    public function sendNotification(User $user): void
    {
        $this->mailer->sendTokenPromotionMail($this->token);
    }
}
