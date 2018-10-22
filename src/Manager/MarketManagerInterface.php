<?php

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;

interface MarketManagerInterface
{
    public function getMarket(Crypto $crypto, Token $token): ?Market;
}
