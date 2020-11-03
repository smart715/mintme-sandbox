<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface UserNotificationChannelManagerInterface
{
    public function getUserNotificationsChannel(User $user): ?array;
}
