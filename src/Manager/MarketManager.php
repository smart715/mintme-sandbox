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

    public function getAllMarkets(CryptoManager $cryptoManager, TokenManager $tokenManager): array
    {
        $cryptos = $cryptoManager->findAll();
        $tokens = $tokenManager->findAll();
        $markets = [];

        foreach ($cryptos as $crypto) {
            foreach ($tokens as $token) {
                $markets[] = $this->getMarket($crypto, $token);
            }
        }

        return $markets;
    }
}
