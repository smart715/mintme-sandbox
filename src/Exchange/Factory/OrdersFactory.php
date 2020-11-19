<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Trade\TraderInterface;

class OrdersFactory implements OrdersFactoryInterface
{
    private TraderInterface $trader;

    public function __construct(TraderInterface $trader)
    {
        $this->trader = $trader;
    }

    public function createInitOrders(User $user, Token $token): void
    {
        return;
    }
}
