<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Token\Token;

interface OrdersFactoryInterface
{
    public function createInitOrders(Token $token): void;
}
