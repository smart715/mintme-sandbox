<?php declare(strict_types = 1);

namespace App\Events;

class DepositCompletedEvent extends TransactionCompletedEvent
{
    public const NAME = "deposit.completed";
    public const TYPE = "deposit";
}
