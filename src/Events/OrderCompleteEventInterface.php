<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Order;

interface OrderCompleteEventInterface
{
    public function getOrder(): Order;
    public function getQuote(): TradebleInterface;
}
