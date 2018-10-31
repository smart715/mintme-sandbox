<?php

namespace App\Manager;

use App\Entity\User;
use App\Exchange\Market;

interface OrderManagerInterface
{
    public function getSellPendingOrdersList(User $user, Market $market): array;

    public function getBuyPendingOrdersList(User $user, Market $market): array;

    public function getPendingOrdersList(User $user, Market $market, string $side): array;

    public function getAllPendingOrders(Market $market, string $side): array;
}
