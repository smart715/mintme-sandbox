<?php declare(strict_types = 1);

namespace App\Notifications\Strategy;

use App\Entity\User;

class NotificationContext
{
    /** @var NotificationStrategyInterface */
    private NotificationStrategyInterface $strategy;

    public function __construct(NotificationStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function sendNotification(User $user): void
    {
        $this->strategy->sendNotification($user);
    }
}
