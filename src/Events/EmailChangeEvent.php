<?php declare(strict_types = 1);

namespace App\Events;

use App\Utils\LockFactory;

class EmailChangeEvent extends UserChangeLockEvent
{
    public function getActionLockKey(): string
    {
        return LockFactory::LOCK_WITHDRAW_AFTER_EMAIL_CHANGE;
    }
}
