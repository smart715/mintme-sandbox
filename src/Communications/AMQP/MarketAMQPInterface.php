<?php declare(strict_types = 1);

namespace App\Communications\AMQP;

use App\Exchange\Market;

interface MarketAMQPInterface
{
    public function send(Market $market): void;
}
