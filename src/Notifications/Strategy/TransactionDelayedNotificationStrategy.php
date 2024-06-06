<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;
use App\Manager\UserNotificationManagerInterface;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use App\Wallet\Model\Type;

class TransactionDelayedNotificationStrategy implements NotificationStrategyInterface
{
    private UserNotificationManagerInterface $userNotificationManager;

    private Type $type;

    public function __construct(
        UserNotificationManagerInterface $userNotificationManager,
        Type $type
    ) {
        $this->userNotificationManager = $userNotificationManager;
        $this->type = $type;
    }

    public function getParentNotificationType(): string
    {
        return Type::DEPOSIT === $this->type->getTypeCode()
            ? NotificationTypes::DEPOSIT
            : NotificationTypes::WITHDRAWAL;
    }

    public function sendNotification(User $user): void
    {
        if ($this->userNotificationManager->isNotificationAvailable(
            $user,
            $this->getParentNotificationType(),
            NotificationChannels::WEBSITE
        )
        ) {
            $this->userNotificationManager->createNotification(
                $user,
                NotificationTypes::TRANSACTION_DELAYED,
                ['type' => $this->type->getTypeCode()]
            );
        }
    }
}
