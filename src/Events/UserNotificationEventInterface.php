<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\User;

interface UserNotificationEventInterface
{
    public function getUser(): User;
    public function getNotificationType(): String;
    public function getExtraData(): array;
}
