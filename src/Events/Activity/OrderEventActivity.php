<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Events\OrderEvent;
use App\Exchange\Order;
use Money\Money;

/** @codeCoverageIgnore */
class OrderEventActivity extends OrderEvent implements ActivityEventInterface
{
    private int $type;

    public function __construct(Order $order, int $type, ?Money $left = null, ?Money $amount = null)
    {
        $this->type = $type;

        parent::__construct($order, $left, $amount);
    }

    public function getType(): int
    {
        return $this->type;
    }
}
