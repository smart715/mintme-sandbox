<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;

class TokenMarketingTipsNotificationStrategy implements NotificationStrategyInterface
{
    private UserNotificationManagerInterface $userNotificationManager;

    private MailerInterface $mailer;

    private string $type;

    private string $timeInterval;

    private array $kbLinks;

    private array $allIntervals;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        string $type,
        string $timeInterval,
        array $kbLinks,
        array $allIntervals
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->type = $type;
        $this->timeInterval = $timeInterval;
        $this->kbLinks = $kbLinks;
        $this->allIntervals = $allIntervals;
    }

    public function sendNotification(User $user): void
    {
        $kbLink = $this->getKbLink($this->timeInterval);

        $jsonData = (array)json_encode([
            'kbLink' => $kbLink,
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
            $this->mailer->sendTokenMarketingTipMail($user, $kbLink);
        }
    }

    private function getKbLink(string $timeInterval): string
    {
        $key = array_search($timeInterval, $this->allIntervals, true);

        return $this->kbLinks[$key] ? : '';
    }
}
