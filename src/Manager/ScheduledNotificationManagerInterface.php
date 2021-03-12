<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ScheduledNotification;
use App\Entity\User;

interface ScheduledNotificationManagerInterface
{
    public function getScheduledNotifications(): ?array;
    public function createScheduledNotification(
        String $notificationType,
        User $user,
        bool $flush = true
    ): ScheduledNotification;
    public function updateScheduledNotification(
        ScheduledNotification $scheduledNotification,
        String $newTimeInterval,
        \DateTimeImmutable $newTimeToBeSend
    ): void;
    public function removeScheduledNotification(int $scheduledNotificationId): int;
}
