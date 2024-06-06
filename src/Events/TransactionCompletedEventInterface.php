<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradableInterface;
use App\Events\Activity\ActivityEventInterface;

interface TransactionCompletedEventInterface extends UserEventInterface, ActivityEventInterface
{
    public function getTradable(): TradableInterface;
    public function getAmount(): string;
}
