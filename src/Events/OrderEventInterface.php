<?php declare(strict_types = 1);

namespace App\Events;

use App\Exchange\Order;

interface OrderEventInterface
{
    public function getOrder(): Order;
}
