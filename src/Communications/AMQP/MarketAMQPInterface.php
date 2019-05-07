<?php

namespace App\Communications\AMQP;

use App\Exchange\Market;

interface MarketAMQPInterface
{
    public function send(Market $market);
}
