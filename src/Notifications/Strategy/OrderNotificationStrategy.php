<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;

class OrderNotificationStrategy implements NotificationStrategyInterface
{
    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    private string $type;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        string $type
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->type = $type;
    }

    public function sendNotification(User $user): void
    {
        $tokenName = $user->getProfile()->getToken()->getName();
        $jsonData = (array)json_encode([
            'tokenName' => $tokenName,
        ], JSON_THROW_ON_ERROR);

        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::WEBSITE
        )
        ) {
            $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        }

        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::EMAIL
        )
        ) {
            $this->mailer->sendNoOrdersMail($user, $tokenName);
        }
    }
}
