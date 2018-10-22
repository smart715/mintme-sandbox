<?php

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;

class MarketManager implements MarketManagerInterface
{
    public function getMarket(Crypto $crypto, Token $token): ?Market
    {
        return new Market($crypto, $token);
    }
}
