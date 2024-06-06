<?php declare(strict_types = 1);

namespace App\Communications\AMQP;

use App\Entity\User;
use App\Exchange\Market;

interface MarketAMQPInterface
{
    public function send(Market $market, ?User $user = null, int $retried = 0): void;
}
