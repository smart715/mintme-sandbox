<?php

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;

interface MarketManagerInterface
{
    public function getMarket(Crypto $crypto, Token $token): ?Market;

    /** @return Market[] */
    public function getAllMarkets(): array;

    /** @return Market[] */
    public function getUserRelatedMarkets(User $user): array;
}
