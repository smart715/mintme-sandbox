<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;
use App\Entity\User;
use Symfony\Component\EventDispatcher\Event;

interface TransactionCompletedEventInterface extends UserEventInterface
{
    public function getTradable(): TradebleInterface;
    public function getAmount(): string;
}
