<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DeployNotification;
use App\Entity\Token\Token;
use App\Entity\User;

interface DeployNotificationManagerInterface
{
    public function createAndNotify(User $notifier, Token $token): void;

    public function alreadyNotified(User $user, Token $token): bool;

    public function findByUserAndToken(User $user, Token $token): ?DeployNotification;
}
