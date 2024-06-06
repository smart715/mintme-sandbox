<?php declare(strict_types = 1);

namespace App\Events;

use App\Utils\LockFactory;

class PasswordChangeEvent extends UserChangeLockEvent
{
    public function getActionLockKey(): string
    {
        return LockFactory::LOCK_WITHDRAW_AFTER_PASSWORD_CHANGE;
    }
}
