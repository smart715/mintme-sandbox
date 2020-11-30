<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;

interface NotificationStrategyInterface
{
    public function notification(User $user): void;
}
