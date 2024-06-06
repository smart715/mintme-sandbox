<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Entity\Token\Token;
use App\Entity\TokenInitOrder;
use App\Entity\User;

interface OrdersFactoryInterface
{
    public function createInitOrders(
        Token $token,
        string $initTokenPrice,
        ?string $priceGrowth,
        string $tokensForSale
    ): void;
    
    public function removeTokenInitOrders(User $user, Token $token, TokenInitOrder $order): void;
}
