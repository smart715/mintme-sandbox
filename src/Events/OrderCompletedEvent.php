<?php declare(strict_types = 1);

namespace App\Events;

use App\Entity\TradebleInterface;
use App\Exchange\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderCompletedEvent extends Event implements OrderCompleteEventInterface
{
    public const CREATED = "order.created";
    public const CANCELLED = "order.cancelled";

    /** @var Order */
    protected Order $order;

    /** @var TradebleInterface */
    protected TradebleInterface $quote;

    public function __construct(Order $order, TradebleInterface $quote)
    {
        $this->order = $order;
        $this->quote = $quote;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getQuote(): TradebleInterface
    {
        return $this->quote;
    }
}
