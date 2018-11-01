<?php

namespace App\Order;

interface OrdersUsersInterface
{
    public function getMakerIds(): array;

    public function getTakerIds(): array;

    public function getAllIds(): array;
}
