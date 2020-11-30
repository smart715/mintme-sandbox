<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Manager\UserNotificationManager;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;

class WithdrawalStrategy implements NotificationStrategyInterface
{
    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    private string $type;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        string $type
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->type = $type;
    }

    public function notification(User $user): void
    {
        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->type,
            NotificationChannels::WEBSITE
        )
        ) {
            $this->userNotificationManager->createNotification($user, $this->type, null);
        }
    }
}
