<?php

namespace App\Order;

use App\Entity\User;
use App\Exchange\Order;

class OrdersUsers implements OrdersUsersInterface
{
    /** @var Order[] */
    private $orders;

    public function __construct(array $orders)
    {
        $this->orders = $orders;
    }

    public function getMakerIds(): array
    {
        return array_unique(
            array_map(function (Order $order) {
                if ($order->getMakerId()) {
                    return $order->getMakerId();
                }
            }, $this->orders)
        );
    }

    public function getTakerIds(): array
    {
        return array_unique(
            array_map(function (Order $order) {
                if ($order->getTakerId()) {
                    return $order->getTakerId();
                }
            }, $this->orders)
        );
    }

    public function getAllIds(): array
    {
        return array_unique(
            array_merge($this->getMakerIds(), $this->getTakerIds())
        );
    }
}
