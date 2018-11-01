<?php

namespace App\Fetcher;

use App\Entity\User;
use App\Exchange\Order;

class OrdersUserFetcher implements OrdersUserFetcherInterface
{
    /** @var Order[] */
    private $orders;

    public function __construct(array $orders)
    {
        $this->orders = $orders;
    }

    public function fetchMakerIds(): array
    {
        return array_unique(
            array_map(function (Order $order) {
                if ($order->getMakerId()) {
                    return $order->getMakerId();
                }
            }, $this->orders)
        );
    }

    public function fetchTakerIds(): array
    {
        return array_unique(
            array_map(function (Order $order) {
                if ($order->getTakerId()) {
                    return $order->getTakerId();
                }
            }, $this->orders)
        );
    }

    public function fetchAllIds(): array
    {
        return array_unique(
            array_merge($this->fetchMakerIds(), $this->fetchTakerIds())
        );
    }
}
