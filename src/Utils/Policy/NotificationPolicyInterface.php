<?php declare(strict_types = 1);

namespace App\Utils\Policy;

use App\Entity\Token\Token;
use App\Entity\User;

interface NotificationPolicyInterface
{
    public function canReceiveNotification(User $user, Token $token): bool;
}
