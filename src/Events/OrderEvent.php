<?php declare(strict_types = 1);

namespace App\Events;

use App\Exchange\Order;
use Money\Money;
use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class OrderEvent extends Event implements OrderEventInterface
{
    public const CREATED = "order.created";
    public const CANCELLED = "order.cancelled";
    public const COMPLETED = "order.completed";

    protected Order $order;
    // Require for placeOrder request. If less than amount then partially executed,
    // if 0 it means that order was fully executed and finished
    protected ?Money $left;

    // Amount that was returned from viabtc (amount with subtracted fee)
    protected ?Money $amount;

    public function __construct(Order $order, ?Money $left = null, ?Money $amount = null)
    {
        $this->order = $order;
        $this->left = $left;
        $this->amount = $amount;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getLeft(): ?Money
    {
        return $this->left;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }
}
