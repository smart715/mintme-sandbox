<?php declare(strict_types = 1);

namespace App\Events;

use App\Config\WithdrawalDelaysConfig;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

abstract class UserChangeLockEvent extends Event
{
    private WithdrawalDelaysConfig $withdrawalDelaysConfig;
    protected User $user;

    public function __construct(
        WithdrawalDelaysConfig $withdrawalDelaysConfig,
        User $user
    ) {
        $this->withdrawalDelaysConfig = $withdrawalDelaysConfig;
        $this->user = $user;
    }

    abstract public function getActionLockKey(): string;

    public function getLockPeriod(): int
    {
        return $this->withdrawalDelaysConfig->getWithdrawAfterUserChangeTime();
    }

    public function getLockKey(): string
    {
        return $this->getActionLockKey() . $this->user->getId();
    }
}
