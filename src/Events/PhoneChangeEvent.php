<?php declare(strict_types = 1);

namespace App\Events;

use App\Utils\LockFactory;

class PhoneChangeEvent extends UserChangeLockEvent
{
    public function getActionLockKey(): string
    {
        return LockFactory::LOCK_WITHDRAW_AFTER_PHONE_CHANGE;
    }
}
