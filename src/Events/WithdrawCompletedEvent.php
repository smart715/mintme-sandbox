<?php declare(strict_types = 1);

namespace App\Events;

class WithdrawCompletedEvent extends TransactionCompletedEvent
{
    public const NAME = "withdraw.completed";
    public const TYPE = "withdraw";
}
