<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;
use App\Exchange\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderEvent extends Event implements OrderEventInterface
{
    public const CREATED = "order.created";
    public const CANCELLED = "order.cancelled";
    public const COMPLETED = "order.completed";

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}
