<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;

class NewInvestorNotificationStrategy implements NotificationStrategyInterface
{
    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    private Token $token;

    private string $type;

    private array $extraData;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        Token $token,
        string $type,
        array $extraData
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->token = $token;
        $this->type = $type;
        $this->extraData = $extraData;
    }

    public function sendNotification(User $user): void
    {
        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::WEBSITE
        )
        ) {
            $jsonData = (array)json_encode(
                $this->extraData,
                JSON_THROW_ON_ERROR
            );
            $this->userNotificationManager->createNotification($user, $this->type, $jsonData);
        }

        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::EMAIL
        )
        ) {
            $this->mailer->sendNewInvestorMail($this->token, $this->extraData['profile']);
        }
    }
}
