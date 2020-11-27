<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use Symfony\Component\HttpFoundation\Request;

interface UserNotificationConfigManagerInterface
{
    public function getUserNotificationsConfig(User $user): ?array;
    public function getOneUserNotificationConfig(User $user, string $type, string $channel): ?array;
    public function updateUserNotificationsConfig(
        User $user,
        array $newConfig
    ): void;
    public function initializeUserNotificationConfig(User $user): void;
}
