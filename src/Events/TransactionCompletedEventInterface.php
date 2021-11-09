<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;

interface TransactionCompletedEventInterface extends UserEventInterface
{
    public function getTradable(): TradebleInterface;
    public function getAmount(): string;
}
