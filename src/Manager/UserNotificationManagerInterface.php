<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface UserNotificationManagerInterface
{
    public function createNotification(
        User $user,
        String $notificationType,
        array $extraData
    ): void;
    public function updateNotifications(User $user): void;
    public function getNotifications(User $user, ?int $notificationLimit): ?array;
}
